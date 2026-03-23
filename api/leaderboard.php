<?php
/**
 * ShipperShop Leaderboard API
 * GET ?type=xp — Top users by XP
 * GET ?type=posts — Top posters this month
 * GET ?type=helpful — Most likes received this month
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
$type = $_GET['type'] ?? 'xp';
$limit = min(intval($_GET['limit'] ?? 20), 50);

api_try_cache('leaderboard_' . $type, 300);

if ($type === 'xp') {
    $users = $d->fetchAll(
        "SELECT ux.user_id, SUM(ux.xp) as total_xp, FLOOR(SUM(ux.xp)/100)+1 as level, u.fullname, u.avatar, u.shipping_company,
                sp.badge as sub_badge
         FROM user_xp ux
         JOIN users u ON ux.user_id = u.id
         LEFT JOIN user_subscriptions us ON us.user_id = u.id AND us.`status` = 'active' AND us.expires_at > NOW()
         LEFT JOIN subscription_plans sp ON sp.id = us.plan_id AND sp.price > 0
         WHERE u.`status` = 'active'
         GROUP BY ux.user_id ORDER BY total_xp DESC LIMIT ?", [$limit]
    );
    success('OK', ['leaderboard' => $users ?: [], 'type' => 'xp']);
}

if ($type === 'posts') {
    $users = $d->fetchAll(
        "SELECT p.user_id, COUNT(*) as post_count, u.fullname, u.avatar, u.shipping_company
         FROM posts p JOIN users u ON p.user_id = u.id
         WHERE p.`status` = 'active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND u.`status` = 'active'
         GROUP BY p.user_id ORDER BY post_count DESC LIMIT ?", [$limit]
    );
    success('OK', ['leaderboard' => $users ?: [], 'type' => 'posts', 'period' => '30 days']);
}

if ($type === 'helpful') {
    $users = $d->fetchAll(
        "SELECT p.user_id, SUM(p.likes_count) as total_likes, COUNT(*) as post_count, u.fullname, u.avatar, u.shipping_company
         FROM posts p JOIN users u ON p.user_id = u.id
         WHERE p.`status` = 'active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND u.`status` = 'active'
         GROUP BY p.user_id ORDER BY total_likes DESC LIMIT ?", [$limit]
    );
    success('OK', ['leaderboard' => $users ?: [], 'type' => 'helpful', 'period' => '30 days']);
}

echo json_encode(['success' => false, 'message' => 'Invalid type']);
