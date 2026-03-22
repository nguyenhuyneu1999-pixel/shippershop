<?php
// ShipperShop API v2 — Parking Finder
// Tinh nang: Tim bai do xe an toan cho shipper
// Save and share safe parking spots for shippers: free/paid, security, capacity
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

function pf2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $key='parking_spots';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $spots=$row?json_decode($row['value'],true):[];
    $district=trim($_GET['district']??'');
    if($district){$spots=array_values(array_filter($spots,function($s) use($district){return mb_stripos($s['district']??'',$district)!==false;}));}
    foreach($spots as &$s){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($s['added_by']??0)]);
        if($u) $s['added_by_name']=$u['fullname'];
    }unset($s);
    $freeCount=count(array_filter($spots,function($s){return ($s['type']??'')==='free';}));
    pf2_ok('OK',['spots'=>array_slice($spots,0,30),'count'=>count($spots),'free'=>$freeCount]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $key='parking_spots';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $spots=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $name=trim($input['name']??'');$address=trim($input['address']??'');
        $district=trim($input['district']??'');$type=$input['type']??'free';
        $price=intval($input['price']??0);$security=!empty($input['security']);
        $capacity=$input['capacity']??'medium';$lat=floatval($input['lat']??0);$lng=floatval($input['lng']??0);
        $notes=trim($input['notes']??'');
        if(!$name||!$address) pf2_ok('Nhap ten va dia chi');
        $maxId=0;foreach($spots as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $spots[]=['id'=>$maxId+1,'name'=>$name,'address'=>$address,'district'=>$district,'type'=>$type,'price'=>$price,'security'=>$security,'capacity'=>$capacity,'lat'=>$lat,'lng'=>$lng,'notes'=>$notes,'added_by'=>$uid,'upvotes'=>0,'downvotes'=>0,'created_at'=>date('c')];
        if(count($spots)>200) pf2_ok('Toi da 200');
    }

    if($action==='vote'){
        $spotId=intval($input['spot_id']??0);$vote=$input['vote']??'up';
        foreach($spots as &$s){if(intval($s['id']??0)===$spotId){if($vote==='up')$s['upvotes']++;else $s['downvotes']++;}}unset($s);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($spots)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($spots))]);
    pf2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
