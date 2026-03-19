<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Find the post
$posts=$d->fetchAll("SELECT p.id, p.user_id, p.likes_count, p.comments_count, p.shares_count, LEFT(p.content,60) as preview,
    (SELECT COUNT(*) FROM likes WHERE post_id=p.id) as real_likes,
    (SELECT COUNT(*) FROM comments WHERE post_id=p.id) as real_comments
    FROM posts p WHERE p.content LIKE '%target 1200%' LIMIT 5");
foreach($posts as $p){
    echo "Post id=".$p['id']." user=".$p['user_id']."\n";
    echo "  posts.likes_count=".$p['likes_count']." | real likes=".$p['real_likes']."\n";
    echo "  posts.comments_count=".$p['comments_count']." | real comments=".$p['real_comments']."\n";
    echo "  posts.shares_count=".$p['shares_count']."\n";
    echo "  preview: ".$p['preview']."\n\n";
}

echo "\n=== 2. Check ALL posts with mismatched counts ===\n";
$mismatched=$d->fetchAll("SELECT p.id, p.likes_count, p.comments_count,
    (SELECT COUNT(*) FROM likes WHERE post_id=p.id) as real_likes,
    (SELECT COUNT(*) FROM comments WHERE post_id=p.id) as real_comments
    FROM posts p
    WHERE p.likes_count != (SELECT COUNT(*) FROM likes WHERE post_id=p.id)
       OR p.comments_count != (SELECT COUNT(*) FROM comments WHERE post_id=p.id)
    LIMIT 20");
echo "Mismatched posts: ".count($mismatched)."\n";
foreach(array_slice($mismatched,0,5) as $m){
    echo "  id=".$m['id'].": likes=".$m['likes_count']."(real:".$m['real_likes'].") cmts=".$m['comments_count']."(real:".$m['real_comments'].")\n";
}

echo "\n=== 3. Total posts with mismatch ===\n";
$total=$d->fetchOne("SELECT COUNT(*) as c FROM posts p
    WHERE p.likes_count != (SELECT COUNT(*) FROM likes WHERE post_id=p.id)
       OR p.comments_count != (SELECT COUNT(*) FROM comments WHERE post_id=p.id)")['c'];
echo "Total mismatched: $total\n";
$totalPosts=$d->fetchOne("SELECT COUNT(*) as c FROM posts")['c'];
echo "Total posts: $totalPosts\n";
