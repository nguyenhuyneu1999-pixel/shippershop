<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db()->getConnection();

// Clean orphan likes (referencing deleted/non-existent posts)
$del1 = $pdo->exec("DELETE l FROM likes l LEFT JOIN posts p ON l.post_id=p.id WHERE p.id IS NULL");
echo "Deleted orphan likes (post missing): $del1\n";

$del2 = $pdo->exec("DELETE l FROM likes l JOIN posts p ON l.post_id=p.id WHERE p.status!='active'");
echo "Deleted likes on deleted posts: $del2\n";

// Clean orphan comments
$del3 = $pdo->exec("DELETE c FROM comments c LEFT JOIN posts p ON c.post_id=p.id WHERE p.id IS NULL");
echo "Deleted orphan comments: $del3\n";

// Resync denormalized counts
$pdo->exec("UPDATE posts p SET 
    likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id),
    comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active')
    WHERE p.status='active'");
echo "Resynced counts for active posts\n";

echo "DONE\n";
