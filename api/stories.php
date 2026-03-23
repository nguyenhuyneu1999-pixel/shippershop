<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$d = db();
$action = $_GET['action'] ?? 'list';

function sAuth() {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $h, $m)) {
        $parts = explode('.', $m[1]);
        if (count($parts) === 3) {
            $p = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return intval($p['user_id'] ?? 0);
        }
    }
    return 0;
}

try {

if ($action === 'list') {
    $uid = sAuth();
    $stories = $d->fetchAll(
        "SELECT s.*, u.fullname as user_name, u.avatar as user_avatar
         FROM stories s JOIN users u ON s.user_id = u.id
         WHERE s.expires_at > NOW()
         ORDER BY s.created_at DESC LIMIT 50"
    );
    
    $grouped = [];
    foreach ($stories ?: [] as $s) {
        $key = $s['user_id'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = ['user_id'=>$s['user_id'],'user_name'=>$s['user_name'],'user_avatar'=>$s['user_avatar'],'stories'=>[]];
        }
        $s['is_viewed'] = false;
        if ($uid) {
            try { $v = $d->fetchOne("SELECT id FROM story_views WHERE story_id = ? AND user_id = ?", [$s['id'], $uid]); $s['is_viewed'] = $v ? true : false; } catch (Throwable $e) {}
        }
        $grouped[$key]['stories'][] = $s;
    }
    success('OK', array_values($grouped));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $uid = sAuth();
    if (!$uid) { error('Auth required', 401); }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $content = trim($input['content'] ?? '');
    $bg = $input['bg_color'] ?? $input['background'] ?? '#7C3AED';
    $imageUrl = null;
    
    if (!empty($_FILES['image'])) {
        $file = $_FILES['image'];
        if ($file['size'] > 5*1024*1024) { error('Max 5MB'); }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) { error('JPG/PNG/WebP only'); }
        $fn = 'story_'.$uid.'_'.time().'.'.$ext;
        if (move_uploaded_file($file['tmp_name'], __DIR__.'/../uploads/posts/'.$fn)) $imageUrl = '/uploads/posts/'.$fn;
    }
    
    if (!$content && !$imageUrl) { error('Thêm nội dung hoặc ảnh'); }
    if (strlen($content) > 200) { error('Tối đa 200 ký tự'); }
    
    $d->query("INSERT INTO stories (user_id, content, image_url, background, expires_at, created_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR), NOW())",
        [$uid, $content, $imageUrl, $bg]);
    success('Story đã đăng!');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'view') {
    $uid = sAuth();
    if (!$uid) { success('OK'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $sid = intval($input['story_id'] ?? 0);
    if ($sid) {
        try { $d->query("INSERT IGNORE INTO story_views (story_id, user_id, viewed_at) VALUES (?, ?, NOW())", [$sid, $uid]); } catch (Throwable $e) {}
        try { $d->query("UPDATE stories SET view_count = view_count + 1 WHERE id = ?", [$sid]); } catch (Throwable $e) {}
    }
    success('OK');
}

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Server error', 'debug' => $e->getMessage()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
