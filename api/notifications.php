<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $userId = getOptionalAuthUserId();
    if (!$userId) { echo json_encode(['success'=>false,'message'=>'Chưa đăng nhập']); exit; }
    
    $likes = $db->fetchAll("
        SELECT 'like' as type, l.created_at, u.fullname as actor_name, u.avatar as actor_avatar,
               p.id as post_id, CONCAT('like_',l.user_id,'_',l.post_id) as notif_key
        FROM likes l JOIN users u ON l.user_id=u.id JOIN posts p ON l.post_id=p.id
        WHERE p.user_id=? AND l.user_id!=?
        ORDER BY l.created_at DESC LIMIT 20
    ", [$userId, $userId]);
    
    $comments = $db->fetchAll("
        SELECT 'comment' as type, c.created_at, u.fullname as actor_name, u.avatar as actor_avatar,
               p.id as post_id, CONCAT('cmt_',c.id) as notif_key
        FROM comments c JOIN users u ON c.user_id=u.id JOIN posts p ON c.post_id=p.id
        WHERE p.user_id=? AND c.user_id!=?
        ORDER BY c.created_at DESC LIMIT 20
    ", [$userId, $userId]);
    
    $all = array_merge($likes, $comments);
    usort($all, function($a,$b){ return strtotime($b['created_at'])-strtotime($a['created_at']); });
    $all = array_slice($all, 0, 30);
    
    // Check which ones are read
    $reads = $db->fetchAll("SELECT notif_key FROM notification_reads WHERE user_id=?", [$userId]);
    $readKeys = array_column($reads, 'notif_key');
    
    foreach ($all as &$n) {
        $n['is_read'] = in_array($n['notif_key'], $readKeys) ? 1 : 0;
    }
    
    echo json_encode(['success'=>true,'data'=>$all]);
    exit;
}

if ($method === 'POST') {
    $userId = getOptionalAuthUserId();
    if (!$userId) { echo json_encode(['success'=>false,'message'=>'Chưa đăng nhập']); exit; }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $key = $input['notif_key'] ?? '';
    if (!$key) { echo json_encode(['success'=>false,'message'=>'Missing notif_key']); exit; }
    
    try {
        $db->insert('notification_reads', ['user_id'=>$userId, 'notif_key'=>$key]);
    } catch(Exception $e) {} // Already read, ignore
    
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Method not allowed']);
