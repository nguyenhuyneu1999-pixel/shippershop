<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();

// Find user nguynvnhuy
$u=$d->fetchOne("SELECT id,fullname,username FROM users WHERE username='nguynvnhuy'");
if(!$u){echo json_encode(['error'=>'not found']);exit;}
$uid=$u['id'];

// 1. Total success (likes from posts + group_posts)
$postLikes=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as c FROM posts WHERE user_id=?",[$uid]);
$gpLikes=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as c FROM group_posts WHERE user_id=?",[$uid]);

// 2. Post count
$postCount=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND status='active'",[$uid]);
$gpCount=$d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE user_id=?",[$uid]);

// 3. Account age
$created=new DateTime($u['created_at'] ?? '');
$now=new DateTime();
$age=$now->diff($created)->days;

// 4. Followers
$followers=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid]);

// 5. Also check post_likes table vs likes table
$plCount=$d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id IN (SELECT id FROM posts WHERE user_id=?)",[$uid]);
$lkCount=$d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE post_id IN (SELECT id FROM posts WHERE user_id=?)",[$uid]);

// 6. Check group_post_likes
$gplCount=$d->fetchOne("SELECT COUNT(*) as c FROM group_post_likes WHERE post_id IN (SELECT id FROM group_posts WHERE user_id=?)",[$uid]);

// 7. Check likes_count field accuracy 
$samplePost=$d->fetchAll("SELECT id, likes_count, (SELECT COUNT(*) FROM post_likes WHERE post_id=p.id) as real_likes FROM posts p WHERE user_id=? AND status='active' ORDER BY likes_count DESC LIMIT 5",[$uid]);
$sampleGP=$d->fetchAll("SELECT id, likes_count, (SELECT COUNT(*) FROM group_post_likes WHERE post_id=gp.id) as real_likes FROM group_posts gp WHERE user_id=? ORDER BY likes_count DESC LIMIT 5",[$uid]);

echo json_encode([
    'user'=>$u,
    'post_likes_sum'=>intval($postLikes['c']),
    'group_post_likes_sum'=>intval($gpLikes['c']),
    'total_success_current'=>intval($postLikes['c'])+intval($gpLikes['c']),
    'post_count'=>intval($postCount['c']),
    'group_post_count'=>intval($gpCount['c']),
    'followers'=>intval($followers['c']),
    'post_likes_table_count'=>intval($plCount['c']),
    'likes_table_count'=>intval($lkCount['c']),
    'group_post_likes_table_count'=>intval($gplCount['c']),
    'sample_posts'=>$samplePost,
    'sample_group_posts'=>$sampleGP
], JSON_PRETTY_PRINT);
