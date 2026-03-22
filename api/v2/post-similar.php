<?php
// ShipperShop API v2 — Similar Posts
// Find posts similar to a given post (keyword matching)
session_start();
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
if(!$postId){echo json_encode(['success'=>true,'data'=>[]]);exit;}

$post=$d->fetchOne("SELECT content,province,user_id FROM posts WHERE id=? AND `status`='active'",[$postId]);
if(!$post){echo json_encode(['success'=>true,'data'=>[]]);exit;}

// Extract keywords
$content=mb_strtolower($post['content']??'');
$words=array_filter(preg_split('/[\s,.\-!?#@]+/u',$content),function($w){return mb_strlen($w)>=3;});
$words=array_slice(array_unique($words),0,5);

$similar=[];
if($words){
    $likes=[];foreach($words as $w){$likes[]="p.content LIKE ?";$params[]='%'.$w.'%';}
    $params[]=$postId;
    $likeStr=implode(' OR ',$likes);
    $similar=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.id!=? AND ($likeStr) ORDER BY (p.likes_count+p.comments_count) DESC LIMIT $limit",array_merge([$postId],$params));
}

// Fallback: same province
if(count($similar)<$limit&&$post['province']){
    $more=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.id!=? AND p.province=? ORDER BY p.created_at DESC LIMIT ?",[$postId,$post['province'],$limit-count($similar)]);
    $existIds=array_column($similar,'id');
    foreach($more as $m){if(!in_array($m['id'],$existIds))$similar[]=$m;}
}

echo json_encode(['success'=>true,'data'=>['similar'=>$similar,'count'=>count($similar)]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
