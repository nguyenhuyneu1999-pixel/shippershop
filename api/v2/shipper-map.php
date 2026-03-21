<?php
// ShipperShop API v2 — Shipper Map Data
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function sm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

if(!$action||$action==='pins'){
    $limit=min(intval($_GET['limit']??50),200);
    $pins=$d->fetchAll("SELECT mp.id,mp.lat,mp.lng,mp.title,mp.address,mp.created_at,u.id as user_id,u.fullname,u.avatar,u.shipping_company FROM map_pins mp JOIN users u ON mp.user_id=u.id WHERE mp.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY mp.created_at DESC LIMIT $limit");
    sm2_ok('OK',['pins'=>$pins,'count'=>count($pins)]);
}

if($action==='province_heat'){
    $data=cache_remember('province_heatmap', function() use($d) {
        return $d->fetchAll("SELECT province,COUNT(*) as posts FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY posts DESC LIMIT 30");
    }, 600);
    sm2_ok('OK',$data);
}

if($action==='company_map'){
    $data=cache_remember('company_map', function() use($d) {
        return $d->fetchAll("SELECT shipping_company,COUNT(*) as shippers FROM users WHERE `status`='active' AND shipping_company IS NOT NULL AND shipping_company!='' GROUP BY shipping_company ORDER BY shippers DESC LIMIT 20");
    }, 600);
    sm2_ok('OK',$data);
}

sm2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
