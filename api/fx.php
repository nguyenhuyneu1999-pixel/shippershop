<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

$xp_cols = $d->fetchAll("SHOW COLUMNS FROM user_xp");
$streak_cols = $d->fetchAll("SHOW COLUMNS FROM user_streaks");

// Today's likes for user 2
$today_likes = $d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= CURDATE()");

echo json_encode([
    'user_xp_cols' => array_map(function($c){return $c['Field'];}, $xp_cols),
    'user_streaks_cols' => array_map(function($c){return $c['Field'];}, $streak_cols),
    'total_likes_today' => $today_likes['c']
]);
