<?php
// ShipperShop API v2 — Conversation Delivery Map
// Share delivery stops/route pins within conversation for coordination
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

function cdm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cdm2_ok('OK',['pins'=>[]]);
    $key='conv_map_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $pins=$row?json_decode($row['value'],true):[];
    foreach($pins as &$p){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($p['user_id']??0)]);
        if($u) $p['user_name']=$u['fullname'];
    }unset($p);
    $completed=count(array_filter($pins,function($p){return !empty($p['completed']);}));
    cdm2_ok('OK',['pins'=>$pins,'count'=>count($pins),'completed'=>$completed]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cdm2_ok('Missing conversation_id');
    $key='conv_map_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $pins=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $label=trim($input['label']??'');
        $address=trim($input['address']??'');
        $lat=floatval($input['lat']??0);
        $lng=floatval($input['lng']??0);
        $pinType=$input['type']??'stop'; // stop, pickup, dropoff, warehouse
        $order=intval($input['order']??count($pins)+1);
        if(!$label&&!$address) cdm2_ok('Nhap ten hoac dia chi');
        $maxId=0;foreach($pins as $p){if(intval($p['id']??0)>$maxId)$maxId=intval($p['id']);}
        $pins[]=['id'=>$maxId+1,'label'=>$label,'address'=>$address,'lat'=>$lat,'lng'=>$lng,'type'=>$pinType,'order'=>$order,'user_id'=>$uid,'completed'=>false,'created_at'=>date('c')];
        if(count($pins)>30) cdm2_ok('Toi da 30 diem');
    }

    if($action==='complete'){
        $pinId=intval($input['pin_id']??0);
        foreach($pins as &$p){if(intval($p['id']??0)===$pinId){$p['completed']=true;$p['completed_at']=date('c');$p['completed_by']=$uid;}}unset($p);
    }

    if($action==='reorder'){
        $newOrder=$input['order']??[];
        foreach($newOrder as $idx=>$pinId){
            foreach($pins as &$p){if(intval($p['id']??0)===intval($pinId)) $p['order']=$idx+1;}unset($p);
        }
        usort($pins,function($a,$b){return intval($a['order']??0)-intval($b['order']??0);});
    }

    if($action==='delete'){
        $pinId=intval($input['pin_id']??0);
        $pins=array_values(array_filter($pins,function($p) use($pinId){return intval($p['id']??0)!==$pinId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($pins)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($pins))]);
    cdm2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
