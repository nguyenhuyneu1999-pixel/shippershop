<?php
// ShipperShop API v2 — Similar Posts
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$postId=intval($_GET['post_id']??0);
$limit=min(intval($_GET['limit']??5),20);
if(!$postId){echo json_encode(['success'=>true,'data'=>['similar'=>[],'count'=>0]]);exit;}

$post=$d->fetchOne("SELECT content,province,user_id FROM posts WHERE id=? AND `status`='active'",[$postId]);
if(!$post){echo json_encode(['success'=>true,'data'=>['similar'=>[],'count'=>0]]);exit;}

$similar=[];

// Try province match first (safe query)
if($post['province']){
    $similar=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.id!=? AND p.province=? ORDER BY (p.likes_count+p.comments_count) DESC LIMIT ?",[$postId,$post['province'],$limit]);
}

// Fallback: recent popular
if(count($similar)<$limit){
    $existIds=$similar?array_column($similar,'id'):[0];
    $placeholders=implode(',',array_fill(0,count($existIds),'?'));
    $params=array_merge($existIds,[$postId,$limit-count($similar)]);
    $more=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.id NOT IN ($placeholders) AND p.id!=? ORDER BY (p.likes_count+p.comments_count) DESC LIMIT ?",$params);
    foreach($more as $m) $similar[]=$m;
}

echo json_encode(['success'=>true,'data'=>['similar'=>array_slice($similar,0,$limit),'count'=>count($similar)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
