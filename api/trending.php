<?php
/**
 * ShipperShop Trending API
 * GET ?action=hashtags — top 10 trending hashtags (24h)
 * GET ?action=topics — trending topics (based on post engagement)
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d = db();
$action = $_GET['action'] ?? 'hashtags';

if ($action === 'hashtags') {
    api_try_cache('trending_hashtags', 300); // 5 min cache
    
    // Extract hashtags from recent posts
    $posts = $d->fetchAll(
        "SELECT content FROM posts WHERE `status` = 'active' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)", []
    );
    
    $tags = [];
    foreach ($posts ?: [] as $post) {
        preg_match_all('/#([a-zA-ZÀ-ỹ0-9_]+)/u', $post['content'], $matches);
        foreach ($matches[1] ?? [] as $tag) {
            $tag = mb_strtolower($tag);
            if (!isset($tags[$tag])) $tags[$tag] = 0;
            $tags[$tag]++;
        }
    }
    
    arsort($tags);
    $top = array_slice($tags, 0, 10, true);
    $result = [];
    foreach ($top as $tag => $count) {
        $result[] = ['tag' => $tag, 'count' => $count];
    }
    
    success('OK', $result);
}

if ($action === 'topics') {
    api_try_cache('trending_topics', 300);
    
    // Top engaged posts in last 24h
    $posts = $d->fetchAll(
        "SELECT p.id, p.content, p.likes_count, p.comments_count, p.type,
                u.fullname as user_name, u.avatar as user_avatar
         FROM posts p LEFT JOIN users u ON p.user_id = u.id
         WHERE p.`status` = 'active' AND p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
         ORDER BY (p.likes_count * 3 + p.comments_count * 5) DESC
         LIMIT 5", []
    );
    
    success('OK', $posts ?: []);
}



if ($action === 'hot_posts') {
    api_try_cache('trending_hot', 120); // 2 min cache
    
    $posts = $d->fetchAll(
        "SELECT p.id, p.content, p.likes_count, p.comments_count, p.views_count,
                u.fullname, u.avatar, u.shipping_company
         FROM posts p JOIN users u ON p.user_id = u.id
         WHERE p.`status` = 'active' AND p.created_at > DATE_SUB(NOW(), INTERVAL 6 HOUR)
         ORDER BY (p.likes_count * 3 + p.comments_count * 5 + p.views_count) DESC
         LIMIT 5", []
    );
    
    success('OK', $posts ?: []);
}
echo json_encode(['success' => false, 'message' => 'Invalid action']);
