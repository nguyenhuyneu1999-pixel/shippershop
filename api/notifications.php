<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api-cache.php';
require_once __DIR__ . '/auth-check.php';
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db = db();
$method = $_SERVER['REQUEST_METHOD'];


$action = $_GET['action'] ?? '';

// Lightweight unread count (for polling badge)

if ($method === 'GET' && $action === 'grouped') {
    $userId = getOptionalAuthUserId();
    if (!$userId) { echo json_encode(['success'=>true,'data'=>[]]); exit; }
    $likeGroups = $db->fetchAll("SELECT p.id as post_id, COUNT(*) as count, GROUP_CONCAT(u.fullname ORDER BY l.created_at DESC SEPARATOR ', ') as actors, MAX(l.created_at) as latest FROM likes l JOIN users u ON l.user_id=u.id JOIN posts p ON l.post_id=p.id WHERE p.user_id=? AND l.user_id!=? AND l.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY p.id ORDER BY latest DESC LIMIT 20", [$userId, $userId]);
    $cmtGroups = $db->fetchAll("SELECT p.id as post_id, COUNT(*) as count, GROUP_CONCAT(DISTINCT u.fullname ORDER BY c.created_at DESC SEPARATOR ', ') as actors, MAX(c.created_at) as latest FROM comments c JOIN users u ON c.user_id=u.id JOIN posts p ON c.post_id=p.id WHERE p.user_id=? AND c.user_id!=? AND c.`status`='active' AND c.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY p.id ORDER BY latest DESC LIMIT 20", [$userId, $userId]);
    $grouped = [];
    foreach ($likeGroups ?: [] as $g) {
        $names = explode(', ', $g['actors']);
        $label = count($names) > 2 ? $names[0] . ', ' . $names[1] . ' va ' . (count($names) - 2) . ' nguoi khac' : $g['actors'];
        $grouped[] = ['type'=>'likes','post_id'=>$g['post_id'],'count'=>intval($g['count']),'actors'=>$label,'latest'=>$g['latest']];
    }
    foreach ($cmtGroups ?: [] as $g) {
        $names = explode(', ', $g['actors']);
        $label = count($names) > 2 ? $names[0] . ', ' . $names[1] . ' va ' . (count($names) - 2) . ' nguoi khac' : $g['actors'];
        $grouped[] = ['type'=>'comments','post_id'=>$g['post_id'],'count'=>intval($g['count']),'actors'=>$label,'latest'=>$g['latest']];
    }
    usort($grouped, function($a, $b) { return strtotime($b['latest']) - strtotime($a['latest']); });
    echo json_encode(['success'=>true,'data'=>array_slice($grouped, 0, 20)]); exit;
}

if ($method === 'GET' && $action === 'unread_count') {
    $userId = getOptionalAuthUserId();
    if ($userId) api_try_cache('notif_count_' . $userId, 5);
    $userId = getOptionalAuthUserId();
    if (!$userId) { echo json_encode(['success'=>true,'data'=>['count'=>0]]); exit; }
    
    // Count recent unread notifications (last 7 days)
    $likeCount = intval($db->fetchOne("SELECT COUNT(*) as c FROM likes l JOIN posts p ON l.post_id=p.id LEFT JOIN notification_reads nr ON nr.notif_key=CONCAT('like_',l.user_id,'_',l.post_id) AND nr.user_id=? WHERE p.user_id=? AND l.user_id!=? AND l.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) AND nr.id IS NULL", [$userId, $userId, $userId])['c'] ?? 0);
    $cmtCount = intval($db->fetchOne("SELECT COUNT(*) as c FROM comments c JOIN posts p ON c.post_id=p.id LEFT JOIN notification_reads nr ON nr.notif_key=CONCAT('cmt_',c.id) AND nr.user_id=? WHERE p.user_id=? AND c.user_id!=? AND c.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) AND c.`status`='active' AND nr.id IS NULL", [$userId, $userId, $userId])['c'] ?? 0);
    $total = $likeCount + $cmtCount;
    
    echo json_encode(['success'=>true,'data'=>['count'=>$total,'likes'=>$likeCount,'comments'=>$cmtCount]]);
    exit;
}

// Mark all as read
if ($method === 'POST' && $action === 'mark_all_read') {
    $userId = getAuthUserId();
    if (!$userId) { echo json_encode(['success'=>false,'message'=>'Auth required']); exit; }
    
    // Get all unread notification keys
    $likes = $db->fetchAll("SELECT CONCAT('like_',l.user_id,'_',l.post_id) as k FROM likes l JOIN posts p ON l.post_id=p.id WHERE p.user_id=? AND l.user_id!=? ORDER BY l.created_at DESC LIMIT 50", [$userId, $userId]);
    $cmts = $db->fetchAll("SELECT CONCAT('cmt_',c.id) as k FROM comments c JOIN posts p ON c.post_id=p.id WHERE p.user_id=? AND c.user_id!=? AND c.`status`='active' ORDER BY c.created_at DESC LIMIT 50", [$userId, $userId]);
    
    $keys = array_merge(array_column($likes, 'k'), array_column($cmts, 'k'));
    foreach ($keys as $key) {
        $exists = $db->fetchOne("SELECT id FROM notification_reads WHERE user_id=? AND notif_key=?", [$userId, $key]);
        if (!$exists) {
            $db->query("INSERT IGNORE INTO notification_reads (user_id, notif_key, created_at) VALUES (?, ?, NOW())", [$userId, $key]);
        }
    }
    
    echo json_encode(['success'=>true,'message'=>'Đã đọc tất cả','data'=>['marked'=>count($keys)]]);
    exit;
}


if ($method === 'GET') {
    $userId = getOptionalAuthUserId();
    if (!$userId) { echo json_encode(['success'=>false,'message'=>'Chưa đăng nhập']); exit; }
    
    $likes = $db->fetchAll("
        SELECT 'like' as type, l.created_at, u.fullname as actor_name, u.avatar as actor_avatar,
               p.id as post_id, CONCAT('like_',l.user_id,'_',l.post_id) as notif_key
        FROM likes l JOIN users u ON l.user_id=u.id JOIN posts p ON l.post_id=p.id
        WHERE p.user_id=? AND l.user_id!=? AND p.status='active'
        ORDER BY l.created_at DESC LIMIT 20
    ", [$userId, $userId]);
    
    $comments = $db->fetchAll("
        SELECT 'comment' as type, c.created_at, u.fullname as actor_name, u.avatar as actor_avatar,
               p.id as post_id, CONCAT('cmt_',c.id) as notif_key
        FROM comments c JOIN users u ON c.user_id=u.id JOIN posts p ON c.post_id=p.id
        WHERE p.user_id=? AND c.user_id!=? AND p.status='active'
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
