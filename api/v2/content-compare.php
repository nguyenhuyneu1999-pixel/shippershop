<?php
// ShipperShop API v2 — Content Compare
// Side-by-side comparison of two posts or two users
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$type=$_GET['type']??'posts';

if($type==='posts'){
    $id1=intval($_GET['id1']??0);$id2=intval($_GET['id2']??0);
    if(!$id1||!$id2){echo json_encode(['success'=>true,'data'=>null]);exit;}
    $p1=$d->fetchOne("SELECT p.*,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$id1]);
    $p2=$d->fetchOne("SELECT p.*,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$id2]);
    if(!$p1||!$p2){echo json_encode(['success'=>true,'data'=>null]);exit;}

    $e1=intval($p1['likes_count'])+intval($p1['comments_count'])+intval($p1['shares_count']);
    $e2=intval($p2['likes_count'])+intval($p2['comments_count'])+intval($p2['shares_count']);

    $comparison=[
        ['metric'=>'Likes','a'=>intval($p1['likes_count']),'b'=>intval($p2['likes_count'])],
        ['metric'=>'Comments','a'=>intval($p1['comments_count']),'b'=>intval($p2['comments_count'])],
        ['metric'=>'Shares','a'=>intval($p1['shares_count']),'b'=>intval($p2['shares_count'])],
        ['metric'=>'Engagement','a'=>$e1,'b'=>$e2],
        ['metric'=>'Length','a'=>mb_strlen($p1['content']),'b'=>mb_strlen($p2['content'])],
    ];

    echo json_encode(['success'=>true,'data'=>['type'=>'posts','a'=>['id'=>$id1,'author'=>$p1['fullname'],'engagement'=>$e1],'b'=>['id'=>$id2,'author'=>$p2['fullname'],'engagement'=>$e2],'comparison'=>$comparison,'winner'=>$e1>$e2?'A':($e2>$e1?'B':'tie')]],JSON_UNESCAPED_UNICODE);exit;
}

if($type==='users'){
    $id1=intval($_GET['id1']??0);$id2=intval($_GET['id2']??0);
    if(!$id1||!$id2){echo json_encode(['success'=>true,'data'=>null]);exit;}
    $u1=$d->fetchOne("SELECT fullname,avatar,total_posts FROM users WHERE id=? AND `status`='active'",[$id1]);
    $u2=$d->fetchOne("SELECT fullname,avatar,total_posts FROM users WHERE id=? AND `status`='active'",[$id2]);
    if(!$u1||!$u2){echo json_encode(['success'=>true,'data'=>null]);exit;}
    $f1=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$id1])['c']);
    $f2=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$id2])['c']);
    $l1=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$id1])['s']);
    $l2=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$id2])['s']);

    $comparison=[
        ['metric'=>'Posts','a'=>intval($u1['total_posts']),'b'=>intval($u2['total_posts'])],
        ['metric'=>'Followers','a'=>$f1,'b'=>$f2],
        ['metric'=>'Total Likes','a'=>$l1,'b'=>$l2],
    ];
    $scoreA=intval($u1['total_posts'])*3+$f1*5+$l1;$scoreB=intval($u2['total_posts'])*3+$f2*5+$l2;

    echo json_encode(['success'=>true,'data'=>['type'=>'users','a'=>['id'=>$id1,'name'=>$u1['fullname'],'score'=>$scoreA],'b'=>['id'=>$id2,'name'=>$u2['fullname'],'score'=>$scoreB],'comparison'=>$comparison,'winner'=>$scoreA>$scoreB?'A':($scoreB>$scoreA?'B':'tie')]],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
