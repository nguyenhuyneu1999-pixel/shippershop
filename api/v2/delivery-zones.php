<?php
// ShipperShop API v2 — Delivery Zones
// Define personal delivery zones with pricing tiers and availability
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function dz_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='delivery_zones_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $zones=$row?json_decode($row['value'],true):[];
    $activeZones=count(array_filter($zones,function($z){return !empty($z['active']);}));
    dz_ok('OK',['zones'=>$zones,'count'=>count($zones),'active'=>$activeZones]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $zones=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $name=trim($input['name']??'');
        $districts=$input['districts']??[];
        $basePrice=intval($input['base_price']??15000);
        $pricePerKm=intval($input['price_per_km']??4000);
        if(!$name) dz_ok('Nhap ten khu vuc');
        $maxId=0;foreach($zones as $z){if(intval($z['id']??0)>$maxId)$maxId=intval($z['id']);}
        $zones[]=['id'=>$maxId+1,'name'=>$name,'districts'=>$districts,'base_price'=>$basePrice,'price_per_km'=>$pricePerKm,'active'=>true,'created_at'=>date('c'),'deliveries'=>0];
        if(count($zones)>20) dz_ok('Toi da 20 khu vuc');
    }

    if($action==='toggle'){
        $zoneId=intval($input['zone_id']??0);
        foreach($zones as &$z){if(intval($z['id']??0)===$zoneId) $z['active']=!($z['active']??true);}unset($z);
    }

    if($action==='delete'){
        $zoneId=intval($input['zone_id']??0);
        $zones=array_values(array_filter($zones,function($z) use($zoneId){return intval($z['id']??0)!==$zoneId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($zones)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($zones))]);
    dz_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
