<?php
// ShipperShop API v2 — User Connections Map
// Shows mutual friends, connection strength, network graph data
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function ucn_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId) ucn_ok('OK',['connections'=>[]]);

// Followers + Following
if(!$action||$action==='network'){
    $followers=$d->fetchAll("SELECT f.follower_id as id,u.fullname,u.avatar,u.shipping_company FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=? AND u.`status`='active' ORDER BY f.created_at DESC LIMIT 50",[$userId]);
    $following=$d->fetchAll("SELECT f.following_id as id,u.fullname,u.avatar,u.shipping_company FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=? AND u.`status`='active' ORDER BY f.created_at DESC LIMIT 50",[$userId]);

    // Find mutuals
    $followerIds=array_column($followers,'id');
    $followingIds=array_column($following,'id');
    $mutualIds=array_intersect($followerIds,$followingIds);

    // Shared groups
    $myGroups=$d->fetchAll("SELECT group_id FROM group_members WHERE user_id=?",[$userId]);
    $myGroupIds=array_column($myGroups,'group_id');

    ucn_ok('OK',['followers'=>count($followers),'following'=>count($following),'mutuals'=>count($mutualIds),'mutual_ids'=>array_values($mutualIds),'groups'=>count($myGroupIds),'top_followers'=>array_slice($followers,0,10),'top_following'=>array_slice($following,0,10)]);
}

// Mutual connections between two users
if($action==='mutual'){
    $otherId=intval($_GET['other_id']??0);
    if(!$otherId) ucn_ok('OK',['mutuals'=>[]]);
    $mutuals=$d->fetchAll("SELECT u.id,u.fullname,u.avatar FROM follows f1 JOIN follows f2 ON f1.following_id=f2.following_id JOIN users u ON f1.following_id=u.id WHERE f1.follower_id=? AND f2.follower_id=? AND f1.following_id!=? AND f1.following_id!=? AND u.`status`='active' LIMIT 20",[$userId,$otherId,$userId,$otherId]);
    ucn_ok('OK',['mutuals'=>$mutuals,'count'=>count($mutuals)]);
}

ucn_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
