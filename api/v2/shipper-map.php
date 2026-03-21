<?php
// ShipperShop API v2 — Shipper Map Data
// Active shippers on map with location, company, status
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

// Active pins on map
if(!$action||$action==='pins'){
    $limit=min(intval($_GET['limit']??50),200);
    $province=$_GET['province']??'';
    $w=["1=1"];$p=[];
    if($province){$w[]="mp.province=?";$p[]=$province;}
    $wc=implode(' AND ',$w);
    $pins=$d->fetchAll("SELECT mp.id,mp.lat,mp.lng,mp.note,mp.province,mp.district,mp.created_at,u.id as user_id,u.fullname,u.avatar,u.shipping_company FROM map_pins mp JOIN users u ON mp.user_id=u.id WHERE $wc AND mp.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY mp.created_at DESC LIMIT $limit",$p);
    sm2_ok('OK',['pins'=>$pins,'count'=>count($pins)]);
}

// Province heatmap
if($action==='province_heat'){
    $data=cache_remember('province_heatmap', function() use($d) {
        $provinces=$d->fetchAll("SELECT province,COUNT(*) as shippers FROM users WHERE `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY shippers DESC");
        return $provinces;
    }, 600);
    sm2_ok('OK',$data);
}

// Company distribution by province
if($action==='company_map'){
    $data=cache_remember('company_province_map', function() use($d) {
        $result=$d->fetchAll("SELECT province,shipping_company,COUNT(*) as count FROM users WHERE `status`='active' AND province IS NOT NULL AND province!='' AND shipping_company IS NOT NULL AND shipping_company!='' GROUP BY province,shipping_company ORDER BY count DESC LIMIT 100");
        return $result;
    }, 600);
    sm2_ok('OK',$data);
}

sm2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
