<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== SYNC likes_count with actual likes table ===\n";

// Find mismatched posts
$mismatched = $d->fetchAll("
    SELECT p.id, p.likes_count as stored, 
           (SELECT COUNT(*) FROM likes WHERE post_id=p.id) as actual
    FROM posts p 
    HAVING stored != actual
    LIMIT 20
");
echo "Mismatched posts: ".count($mismatched)."\n";
foreach($mismatched as $m){
    echo "  post ".$m['id'].": stored=".$m['stored']." actual=".$m['actual']."\n";
}

// Fix ALL posts
$d->query("UPDATE posts p SET likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = p.id)");
echo "\nFixed ALL posts.likes_count\n";

// Also fix comments_count
$d->query("UPDATE posts p SET comments_count = (SELECT COUNT(*) FROM comments WHERE post_id = p.id)");
echo "Fixed ALL posts.comments_count\n";

// Verify
$stillBad = $d->fetchOne("
    SELECT COUNT(*) as c FROM posts p 
    WHERE likes_count != (SELECT COUNT(*) FROM likes WHERE post_id=p.id)
");
echo "\nStill mismatched after fix: ".$stillBad['c']."\n";

echo "DONE\n";
