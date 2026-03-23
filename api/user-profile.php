<?php
/**
 * User Public Profile API
 * GET /api/user-profile.php?id=5 - Get user profile + posts
 * GET /api/user-profile.php?id=5&tab=saved - Get saved posts
 * GET /api/user-profile.php?id=5&tab=liked - Get liked posts
 * GET /api/user-profile.php?id=5&tab=commented - Get commented posts
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$db = db();
$userId = intval($_GET['id'] ?? 0);
$tab = $_GET['tab'] ?? 'posts';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

if ($userId <= 0) { echo json_encode(['success' => false, 'message' => 'User ID required']); exit; }

// Get user info
$user = $db->fetchOne(
    "SELECT id, username, fullname, avatar, bio, shipping_company, created_at FROM users WHERE id = ? AND status = 'active'",
    [$userId]
);
if (!$user) { echo json_encode(['success' => false, 'message' => 'User not found']); exit; }

// Stats
$user['stats'] = [
    'posts' => intval($db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND status = 'active'", [$userId])['c']),
    'likes' => intval($db->fetchOne("SELECT COUNT(*) as c FROM likes WHERE user_id = ?", [$userId])['c']),
    'comments' => intval($db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id = ? AND status = 'active'", [$userId])['c']),
];
try {
    $user['stats']['followers'] = intval($db->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id = ?", [$userId])['c']);
    $user['stats']['following'] = intval($db->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id = ?", [$userId])['c']);
} catch (Exception $e) { $user['stats']['followers'] = 0; $user['stats']['following'] = 0; }

// Check if viewing user follows this user
$authUid = getAuthUserId();
$user['is_following'] = false;
if ($authUid) {
    try {
        $f = $db->fetchOne("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?", [$authUid, $userId]);
        $user['is_following'] = $f ? true : false;
    } catch (Exception $e) {}
}

// Get posts based on tab
$items = [];
if ($tab === 'posts') {
    $items = $db->fetchAll(
        "SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.username as user_username,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
         (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count
         FROM posts p LEFT JOIN users u ON p.user_id = u.id
         WHERE p.user_id = ? AND p.status = 'active' ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset",
        [$userId]
    );
} elseif ($tab === 'liked') {
    $items = $db->fetchAll(
        "SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.username as user_username,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
         (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count
         FROM likes l JOIN posts p ON l.post_id = p.id LEFT JOIN users u ON p.user_id = u.id
         WHERE l.user_id = ? AND p.status = 'active' ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset",
        [$userId]
    );
} elseif ($tab === 'saved') {
    $items = $db->fetchAll(
        "SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.username as user_username,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
         (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count
         FROM saved_posts s JOIN posts p ON s.post_id = p.id LEFT JOIN users u ON p.user_id = u.id
         WHERE s.user_id = ? AND p.status = 'active' ORDER BY s.created_at DESC LIMIT $limit OFFSET $offset",
        [$userId]
    );
} elseif ($tab === 'commented') {
    $items = $db->fetchAll(
        "SELECT DISTINCT p.*, u.fullname as user_name, u.avatar as user_avatar, u.username as user_username,
         (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
         (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count
         FROM comments c JOIN posts p ON c.post_id = p.id LEFT JOIN users u ON p.user_id = u.id
         WHERE c.user_id = ? AND p.status = 'active' AND c.status = 'active' ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset",
        [$userId]
    );
}

// Add user_liked/user_saved status
if ($authUid) {
    foreach ($items as &$item) {
        $liked = $db->fetchOne("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", [$item['id'], $authUid]);
        $item['user_liked'] = $liked ? true : false;
        try {
            $saved = $db->fetchOne("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?", [$item['id'], $authUid]);
            $item['user_saved'] = $saved ? true : false;
        } catch (Exception $e) { $item['user_saved'] = false; }
    }
}



// Upload cover photo
if ($action === 'upload_cover') {
    $uid = getAuthUserId();
    if (!$uid) { error('Auth required', 401); }
    
    if (empty($_FILES['cover'])) { error('No file uploaded'); }
    
    $file = $_FILES['cover'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) { error('File quá lớn (tối đa 5MB)'); }
    
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) { error('Chỉ chấp nhận JPEG, PNG, WebP'); }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . $uid . '_' . time() . '.' . $ext;
    $path = __DIR__ . '/../uploads/avatars/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $path)) { error('Upload failed'); }
    
    $coverUrl = '/uploads/avatars/' . $filename;
    $d->query("UPDATE users SET cover_image = ? WHERE id = ?", [$coverUrl, $uid]);
    
    // Update localStorage hint
    success('Đã cập nhật ảnh bìa!', ['cover_image' => $coverUrl]);
}

echo json_encode(['success' => true, 'data' => ['user' => $user, 'items' => $items, 'tab' => $tab]]);