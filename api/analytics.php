<?php
/**
 * ShipperShop User Analytics API
 * GET ?action=overview — site-wide stats
 * GET ?action=user_stats&id=X — individual user stats
 * GET ?action=growth — growth metrics
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
$action = $_GET['action'] ?? 'overview';

if ($action === 'overview') {
    api_try_cache('analytics_overview', 300);
    
    $data = [
        'total_users' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status` = 'active'")['c']),
        'total_posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status` = 'active'")['c']),
        'total_comments' => intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE `status` = 'active'")['c']),
        'total_groups' => intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups` WHERE `status` = 'active'")['c']),
        'total_likes' => intval($d->fetchOne("SELECT COUNT(*) as c FROM likes")['c']),
        'online_now' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online = 1")['c']),
        'posts_today' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE DATE(created_at) = CURDATE() AND `status` = 'active'")['c']),
        'new_users_today' => intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE DATE(created_at) = CURDATE()")['c']),
        'active_subscriptions' => intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status` = 'active' AND expires_at > NOW()")['c']),
    ];
    
    success('OK', $data);
}

if ($action === 'growth') {
    api_try_cache('analytics_growth', 600);
    $days = min(intval($_GET['days'] ?? 30), 90);
    
    $users = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date", [$days]);
    $posts = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as count FROM posts WHERE `status` = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date", [$days]);
    $engagement = $d->fetchAll("SELECT DATE(created_at) as date, COUNT(*) as count FROM likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(created_at) ORDER BY date", [$days]);
    
    success('OK', ['users' => $users ?: [], 'posts' => $posts ?: [], 'engagement' => $engagement ?: [], 'days' => $days]);
}

if ($action === 'user_stats') {
    $uid = intval($_GET['id'] ?? 0);
    if (!$uid) { error('Missing id'); }
    api_try_cache('analytics_user_' . $uid, 120);
    
    $stats = [
        'posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND `status` = 'active'", [$uid])['c']),
        'comments' => intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id = ? AND `status` = 'active'", [$uid])['c']),
        'likes_received' => intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as c FROM posts WHERE user_id = ? AND `status` = 'active'", [$uid])['c']),
        'likes_given' => intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE user_id = ?", [$uid])['c']),
        'followers' => intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id = ?", [$uid])['c']),
        'following' => intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id = ?", [$uid])['c']),
        'groups' => intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id = ?", [$uid])['c']),
    ];
    
    success('OK', $stats);
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
