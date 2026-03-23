<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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

// Helper
function sGetAuth() {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/', $header, $m)) {
        $parts = explode('.', $m[1]);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            return intval($payload['user_id'] ?? 0);
        }
    }
    return 0;
}

if ($action === 'list') {
    $uid = sGetAuth();
    
    $stories = $d->fetchAll(
        "SELECT s.*, u.fullname as user_name, u.avatar as user_avatar,
                (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count
         FROM stories s
         JOIN users u ON s.user_id = u.id
         WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND s.`status` = 'active'
         ORDER BY s.created_at DESC LIMIT 50"
    );
    
    $grouped = [];
    foreach ($stories ?: [] as $s) {
        $key = $s['user_id'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'user_id' => $s['user_id'],
                'user_name' => $s['user_name'],
                'user_avatar' => $s['user_avatar'],
                'stories' => [],
            ];
        }
        if ($uid) {
            $viewed = $d->fetchOne("SELECT id FROM story_views WHERE story_id = ? AND user_id = ?", [$s['id'], $uid]);
            $s['is_viewed'] = $viewed ? true : false;
        }
        $grouped[$key]['stories'][] = $s;
    }
    
    success('OK', array_values($grouped));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $uid = sGetAuth();
    if (!$uid) { error('Auth required', 401); }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;
    $content = trim($input['content'] ?? '');
    $bgColor = $input['bg_color'] ?? '#7C3AED';
    $imageUrl = null;
    
    if (!empty($_FILES['image'])) {
        $file = $_FILES['image'];
        if ($file['size'] > 5 * 1024 * 1024) { error('Max 5MB'); }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) { error('JPG/PNG/WebP only'); }
        $filename = 'story_' . $uid . '_' . time() . '.' . $ext;
        $path = __DIR__ . '/../uploads/posts/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $path)) $imageUrl = '/uploads/posts/' . $filename;
    }
    
    if (!$content && !$imageUrl) { error('Thêm nội dung hoặc ảnh'); }
    if (strlen($content) > 200) { error('Story tối đa 200 ký tự'); }
    
    $d->query("INSERT INTO stories (user_id, content, image_url, bg_color, `status`, created_at) VALUES (?, ?, ?, ?, 'active', NOW())",
        [$uid, $content, $imageUrl, $bgColor]);
    
    success('Story đã đăng!');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'view') {
    $uid = sGetAuth();
    if (!$uid) { success('OK'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $storyId = intval($input['story_id'] ?? 0);
    if ($storyId) {
        try { $d->query("INSERT IGNORE INTO story_views (story_id, user_id, viewed_at) VALUES (?, ?, NOW())", [$storyId, $uid]); } catch (Throwable $e) {}
    }
    success('OK');
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
