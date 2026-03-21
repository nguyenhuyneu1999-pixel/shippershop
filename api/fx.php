<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();

// Fix likes_count mismatches
$d->query("UPDATE posts p SET likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id) WHERE p.`status` = 'active'");

// Fix comments_count
$d->query("UPDATE posts p SET comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND `status` = 'active') WHERE p.`status` = 'active'");

// Verify
$bad = $d->fetchAll("SELECT p.id, p.likes_count, COUNT(l.id) as real FROM posts p LEFT JOIN likes l ON l.post_id = p.id WHERE p.`status`='active' GROUP BY p.id HAVING ABS(p.likes_count - COUNT(l.id)) > 0 LIMIT 5");

echo json_encode(['fixed' => true, 'remaining_mismatches' => count($bad)]);
