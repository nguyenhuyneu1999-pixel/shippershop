<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$d=db();
$seedMin=3;$seedMax=102;

// Test users endpoint
$filter=$_GET['t']??'users';

if($filter==='users'){
    $users=$d->fetchAll("SELECT u.id,u.username,u.fullname,u.avatar,u.role,u.shipping_company,u.created_at,
        (SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active') as post_count,
        (SELECT COUNT(*) FROM comments WHERE user_id=u.id) as comment_count,
        (SELECT COUNT(*) FROM likes WHERE user_id=u.id) as like_count
    FROM users u WHERE (u.id=2 OR u.id>$seedMax) ORDER BY u.created_at DESC LIMIT 5");
    $realC=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE (id=2 OR id>$seedMax)")['c'];
    $seedC=$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE id>=$seedMin AND id<=$seedMax")['c'];
    echo json_encode(['success'=>true,'data'=>['users'=>$users,'counts'=>['all'=>$realC+$seedC,'real'=>$realC,'seed'=>$seedC]]]);
}
if($filter==='posts'){
    $posts=$d->fetchAll("SELECT p.id,LEFT(p.content,80) as content,p.likes_count,p.comments_count,p.created_at,
        u.fullname as user_name,u.avatar as user_avatar,
        CASE WHEN u.id>=$seedMin AND u.id<=$seedMax THEN 1 ELSE 0 END as is_seed
    FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND (p.user_id=2 OR p.user_id>$seedMax)
    ORDER BY p.created_at DESC LIMIT 5");
    $realC=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND (user_id=2 OR user_id>$seedMax)")['c'];
    $seedC=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND user_id>=$seedMin AND user_id<=$seedMax")['c'];
    echo json_encode(['success'=>true,'data'=>['posts'=>$posts,'counts'=>['all'=>$realC+$seedC,'real'=>$realC,'seed'=>$seedC]]]);
}
