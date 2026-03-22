<?php
// ShipperShop API v2 — Post Engagement Compare
// Compare engagement metrics between two posts or two users
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function ec2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Compare two posts
if(!$action||$action==='posts'){
    $id1=intval($_GET['post1']??0);$id2=intval($_GET['post2']??0);
    if(!$id1||!$id2) ec2_ok('OK',['error'=>'Need post1 and post2']);
    $p1=$d->fetchOne("SELECT p.id,p.content,p.likes_count,p.comments_count,p.shares_count,p.view_count,p.created_at,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$id1]);
    $p2=$d->fetchOne("SELECT p.id,p.content,p.likes_count,p.comments_count,p.shares_count,p.view_count,p.created_at,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$id2]);
    if(!$p1||!$p2) ec2_ok('Post not found');

    $eng1=intval($p1['likes_count'])+intval($p1['comments_count'])+intval($p1['shares_count']);
    $eng2=intval($p2['likes_count'])+intval($p2['comments_count'])+intval($p2['shares_count']);
    $winner=$eng1>$eng2?1:($eng2>$eng1?2:0);

    ec2_ok('OK',['post1'=>$p1,'post2'=>$p2,'engagement1'=>$eng1,'engagement2'=>$eng2,'winner'=>$winner]);
}

// Compare two users (30-day stats)
if($action==='users'){
    $u1=intval($_GET['user1']??0);$u2=intval($_GET['user2']??0);
    if(!$u1||!$u2) ec2_ok('OK',['error'=>'Need user1 and user2']);

    $stats=[];
    foreach([$u1,$u2] as $uid){
        $u=$d->fetchOne("SELECT fullname,avatar,total_posts,total_success FROM users WHERE id=? AND `status`='active'",[$uid]);
        $posts30=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$uid])['c']);
        $likes30=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$uid])['s']);
        $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);
        $stats[]=['user'=>$u,'posts_30d'=>$posts30,'likes_30d'=>$likes30,'followers'=>$followers,'engagement'=>$posts30*2+$likes30+$followers*3];
    }

    ec2_ok('OK',['user1'=>$stats[0],'user2'=>$stats[1],'winner'=>$stats[0]['engagement']>$stats[1]['engagement']?1:($stats[1]['engagement']>$stats[0]['engagement']?2:0)]);
}

ec2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
