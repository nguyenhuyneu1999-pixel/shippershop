<?php
/**
 * ShipperShop Global Search — Search across posts, users, groups
 * GET /api/search.php?q=giao+hang&type=all&limit=10
 * Types: all, posts, users, groups
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';
$limit = min(intval($_GET['limit'] ?? 10), 30);

if (strlen($q) < 2) {
    echo json_encode(['success' => false, 'message' => 'Tối thiểu 2 ký tự']);
    exit;
}

// Cache search results 10s
api_try_cache('search_' . md5($q . $type . $limit), 10);

$d = db();
$results = [];

// Search posts (FULLTEXT)
if ($type === 'all' || $type === 'posts') {
    try {
        $posts = $d->fetchAll(
            "SELECT p.id, p.content, p.likes_count, p.comments_count, p.created_at,
                    u.fullname as user_name, u.avatar as user_avatar
             FROM posts p LEFT JOIN users u ON p.user_id = u.id
             WHERE p.`status` = 'active' AND MATCH(p.content) AGAINST(? IN BOOLEAN MODE)
             ORDER BY p.likes_count DESC, p.created_at DESC LIMIT ?",
            [$q . '*', $limit]
        );
        $results['posts'] = $posts ?: [];
    } catch (Throwable $e) {
        // Fallback to LIKE if FULLTEXT fails
        $posts = $d->fetchAll(
            "SELECT p.id, p.content, p.likes_count, p.comments_count, p.created_at,
                    u.fullname as user_name, u.avatar as user_avatar
             FROM posts p LEFT JOIN users u ON p.user_id = u.id
             WHERE p.`status` = 'active' AND p.content LIKE ?
             ORDER BY p.created_at DESC LIMIT ?",
            ['%' . $q . '%', $limit]
        );
        $results['posts'] = $posts ?: [];
    }
}

// Search users
if ($type === 'all' || $type === 'users') {
    try {
        $users = $d->fetchAll(
            "SELECT id, fullname, username, avatar, shipping_company,
                    (SELECT COUNT(*) FROM follows WHERE following_id = users.id) as follower_count
             FROM users WHERE MATCH(fullname, username) AGAINST(? IN BOOLEAN MODE) AND `status` = 'active'
             ORDER BY follower_count DESC LIMIT ?",
            [$q . '*', $limit]
        );
        $results['users'] = $users ?: [];
    } catch (Throwable $e) {
        $users = $d->fetchAll(
            "SELECT id, fullname, username, avatar, shipping_company FROM users
             WHERE (fullname LIKE ? OR username LIKE ?) AND `status` = 'active' LIMIT ?",
            ['%' . $q . '%', '%' . $q . '%', $limit]
        );
        $results['users'] = $users ?: [];
    }
}

// Search groups
if ($type === 'all' || $type === 'groups') {
    $groups = $d->fetchAll(
        "SELECT id, name, slug, description, icon_image, member_count
         FROM `groups` WHERE `status` = 'active' AND (name LIKE ? OR description LIKE ?)
         ORDER BY member_count DESC LIMIT ?",
        ['%' . $q . '%', '%' . $q . '%', $limit]
    );
    $results['groups'] = $groups ?: [];
}

$totalResults = 0;
foreach ($results as $arr) $totalResults += count($arr);

success('OK', ['results' => $results, 'total' => $totalResults, 'query' => $q]);
