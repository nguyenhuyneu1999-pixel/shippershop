<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

// Test direct search
$r1 = $d->fetchAll("SELECT id, LEFT(content,50) as txt FROM posts WHERE content LIKE ? LIMIT 3", ['%ship%']);
$r2 = $d->fetchAll("SELECT id, LEFT(content,50) as txt FROM posts WHERE MATCH(content) AGAINST(? IN BOOLEAN MODE) LIMIT 3", ['ship*']);

echo json_encode([
    'like_results' => count($r1 ?: []),
    'fulltext_results' => count($r2 ?: []),
    'like_sample' => $r1 ? $r1[0] : null,
]);
