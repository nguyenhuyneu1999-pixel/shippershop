<?php
/**
 * ShipperShop Stories (24h expiry)
 * GET ?action=list — active stories (24h)
 * POST ?action=create — create story (text/image)
 * POST ?action=view — mark story viewed
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
try { require_once __DIR__ . '/auth-check.php'; } catch (Throwable $e) {}
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$d = db();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $uid = getOptionalAuthUserId();
    api_try_cache('stories_feed', 30);
    
    // Get stories from last 24h, grouped by user
    $stories = $d->fetchAll(
        "SELECT s.*, u.fullname as user_name, u.avatar as user_avatar,
                (SELECT COUNT(*) FROM story_views WHERE story_id = s.id) as view_count
         FROM stories s
         JOIN users u ON s.user_id = u.id
         WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND s.`status` = 'active'
         ORDER BY s.created_at DESC LIMIT 50"
    );
    
    // Group by user
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
        // Check if viewed
        if ($uid) {
            $viewed = $d->fetchOne("SELECT id FROM story_views WHERE story_id = ? AND user_id = ?", [$s['id'], $uid]);
            $s['is_viewed'] = $viewed ? true : false;
        }
        $grouped[$key]['stories'][] = $s;
    }
    
    success('OK', array_values($grouped));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $uid = getAuthUserId();
    if (!$uid) { error('Auth required', 401); }
    
    $content = '';
    $imageUrl = null;
    $bgColor = '#7C3AED';
    
    // Text story
    if (!empty($_POST['content']) || !empty(json_decode(file_get_contents('php://input'), true)['content'])) {
        $input = !empty($_POST['content']) ? $_POST : json_decode(file_get_contents('php://input'), true);
        $content = trim($input['content'] ?? '');
        $bgColor = $input['bg_color'] ?? '#7C3AED';
        if (strlen($content) > 200) { error('Story tối đa 200 ký tự'); }
    }
    
    // Image story
    if (!empty($_FILES['image'])) {
        $file = $_FILES['image'];
        if ($file['size'] > 5 * 1024 * 1024) { error('Max 5MB'); }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) { error('JPG/PNG/WebP only'); }
        $filename = 'story_' . $uid . '_' . time() . '.' . $ext;
        $path = __DIR__ . '/../uploads/posts/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $path)) {
            $imageUrl = '/uploads/posts/' . $filename;
        }
        $content = trim($_POST['content'] ?? '');
    }
    
    if (!$content && !$imageUrl) { error('Thêm nội dung hoặc ảnh'); }
    
    $d->query("INSERT INTO stories (user_id, content, image_url, bg_color, `status`, created_at) VALUES (?, ?, ?, ?, 'active', NOW())",
        [$uid, $content, $imageUrl, $bgColor]);
    
    // Award XP
    try { awardXP($uid, 'story', 5, 'Đăng story'); } catch (Throwable $e) {}
    
    api_cache_flush('stories_');
    success('Story đã đăng! +5 XP');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'view') {
    $uid = getOptionalAuthUserId();
    if (!$uid) { success('OK'); }
    $input = json_decode(file_get_contents('php://input'), true);
    $storyId = intval($input['story_id'] ?? 0);
    if ($storyId) {
        try {
            $d->query("INSERT IGNORE INTO story_views (story_id, user_id, viewed_at) VALUES (?, ?, NOW())", [$storyId, $uid]);
        } catch (Throwable $e) {}
    }
    success('OK');
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
