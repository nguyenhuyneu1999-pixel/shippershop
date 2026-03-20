<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Sync total_success and total_posts for ALL users
$pdo->exec("UPDATE users u SET 
  total_success = (
    COALESCE((SELECT SUM(likes_count) FROM posts WHERE user_id=u.id AND `status`='active'),0) +
    COALESCE((SELECT SUM(likes_count) FROM group_posts WHERE user_id=u.id AND `status`='active'),0)
  ),
  total_posts = (
    COALESCE((SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active'),0) +
    COALESCE((SELECT COUNT(*) FROM group_posts WHERE user_id=u.id AND `status`='active'),0)
  )
");

// Sync comments.likes_count from comment_likes
$pdo->exec("UPDATE comments c SET likes_count = COALESCE((SELECT COUNT(*) FROM comment_likes WHERE comment_id=c.id),0)");

// Verify
$d = db();
$sample = $d->fetchAll("SELECT id, fullname, total_success, total_posts FROM users WHERE total_posts > 0 ORDER BY total_success DESC LIMIT 10");
$total_users = $d->fetchOne("SELECT COUNT(*) as c FROM users WHERE total_posts > 0")['c'];

echo json_encode([
    'step' => 'sync_counts',
    'status' => 'OK',
    'users_with_posts' => intval($total_users),
    'top_users' => $sample
], JSON_PRETTY_PRINT);
