<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();

// Check slow queries (tables without proper indexes for common queries)
$checks = [];

// Posts feed: ORDER BY created_at DESC with status filter
$r = $pdo->query("EXPLAIN SELECT p.* FROM posts p WHERE p.`status`='active' ORDER BY p.created_at DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);
$checks['feed_default'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Hot sort
$r = $pdo->query("EXPLAIN SELECT p.* FROM posts p WHERE p.`status`='active' ORDER BY (p.likes_count*3+p.comments_count*5) DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);
$checks['feed_hot'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// User posts
$r = $pdo->query("EXPLAIN SELECT * FROM posts WHERE user_id=2 AND `status`='active' ORDER BY created_at DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);
$checks['user_posts'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Notifications (expensive: joins + subqueries)
$r = $pdo->query("EXPLAIN SELECT l.created_at, u.fullname FROM likes l JOIN users u ON l.user_id=u.id JOIN posts p ON l.post_id=p.id WHERE p.user_id=2 AND l.user_id!=2 ORDER BY l.created_at DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);
$checks['notif_likes'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Comments count
$r = $pdo->query("EXPLAIN SELECT COUNT(*) FROM comments WHERE post_id=1038 AND `status`='active'")->fetch(PDO::FETCH_ASSOC);
$checks['comment_count'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Conversations list
$r = $pdo->query("EXPLAIN SELECT * FROM conversations WHERE (user1_id=2 OR user2_id=2) AND `status`='active' ORDER BY last_message_at DESC")->fetch(PDO::FETCH_ASSOC);
$checks['conversations'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Group posts
$r = $pdo->query("EXPLAIN SELECT gp.* FROM group_posts gp WHERE gp.group_id=1 AND gp.`status`='active' ORDER BY gp.created_at DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);
$checks['group_posts'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Followers count
$r = $pdo->query("EXPLAIN SELECT COUNT(*) FROM follows WHERE following_id=2")->fetch(PDO::FETCH_ASSOC);
$checks['followers'] = ['rows' => $r['rows'], 'key' => $r['key'], 'type' => $r['type']];

// Table sizes
$sizes = $pdo->query("SELECT TABLE_NAME, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_ROWS DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['checks' => $checks, 'top_tables' => $sizes], JSON_PRETTY_PRINT);
