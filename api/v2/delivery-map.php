<?php
// ShipperShop API v2 — Delivery Map
// Map visualization of shipper delivery areas + hotspots
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function dm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Delivery hotspots by province
if(!$action||$action==='hotspots'){
    $data=cache_remember('delivery_hotspots', function() use($d) {
        $byProvince=$d->fetchAll("SELECT province,COUNT(*) as posts,SUM(likes_count) as engagement FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY posts DESC LIMIT 20");
        $byDistrict=$d->fetchAll("SELECT province,district,COUNT(*) as posts FROM posts WHERE `status`='active' AND district IS NOT NULL AND district!='' GROUP BY province,district ORDER BY posts DESC LIMIT 30");
        $totalWithLocation=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!=''")['c']);
        return ['by_province'=>$byProvince,'by_district'=>$byDistrict,'total_with_location'=>$totalWithLocation];
    }, 600);
    dm_ok('OK',$data);
}

// User's delivery areas
if($action==='user'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) dm_ok('OK',['areas'=>[]]);
    $areas=$d->fetchAll("SELECT province,district,COUNT(*) as posts FROM posts WHERE user_id=? AND `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province,district ORDER BY posts DESC LIMIT 15",[$userId]);
    dm_ok('OK',['areas'=>$areas,'count'=>count($areas)]);
}

// Active shippers by area
if($action==='shippers'){
    $province=$_GET['province']??'';
    if(!$province) dm_ok('OK',['shippers'=>[]]);
    $shippers=$d->fetchAll("SELECT DISTINCT p.user_id,u.fullname,u.avatar,u.shipping_company,COUNT(p.id) as post_count FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.province=? AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY p.user_id ORDER BY post_count DESC LIMIT 20",[$province]);
    dm_ok('OK',['shippers'=>$shippers,'province'=>$province]);
}

dm_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
