<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();

$r1 = $pdo->query("EXPLAIN SELECT p.* FROM posts p WHERE p.`status`='active' ORDER BY p.created_at DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);
$r2 = $pdo->query("EXPLAIN SELECT p.* FROM posts p WHERE p.`status`='active' ORDER BY p.hot_score DESC LIMIT 20")->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'feed_new' => ['rows' => $r1['rows'], 'key' => $r1['key'], 'type' => $r1['type']],
    'feed_hot' => ['rows' => $r2['rows'], 'key' => $r2['key'], 'type' => $r2['type']],
]);
