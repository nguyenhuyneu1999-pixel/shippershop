<?php
// ShipperShop API v2 — Conversation Incident Report
// Tinh nang: Bao cao su co giao hang trong cuoc hoi thoai
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

$INCIDENT_TYPES=[
    ['id'=>'damaged','name'=>'Hang hu hong','icon'=>'📦💥','severity'=>'high'],
    ['id'=>'lost','name'=>'Mat hang','icon'=>'❌📦','severity'=>'critical'],
    ['id'=>'wrong_address','name'=>'Sai dia chi','icon'=>'📍❌','severity'=>'medium'],
    ['id'=>'customer_absent','name'=>'Khach vang','icon'=>'🏠❌','severity'=>'low'],
    ['id'=>'refused','name'=>'Khach tu choi','icon'=>'🙅','severity'=>'medium'],
    ['id'=>'accident','name'=>'Tai nan','icon'=>'🚨','severity'=>'critical'],
    ['id'=>'weather','name'=>'Thoi tiet xau','icon'=>'⛈️','severity'=>'medium'],
    ['id'=>'vehicle','name'=>'Hong xe','icon'=>'🔧','severity'=>'high'],
    ['id'=>'other','name'=>'Khac','icon'=>'📋','severity'=>'low'],
];

function cir_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cir_ok('OK',['incidents'=>[],'types'=>$INCIDENT_TYPES]);
    $key='conv_incidents_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $incidents=$row?json_decode($row['value'],true):[];
    foreach($incidents as &$inc){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($inc['reporter_id']??0)]);
        if($u) $inc['reporter_name']=$u['fullname'];
        // Enrich type info
        foreach($INCIDENT_TYPES as $it){if($it['id']===($inc['type']??'')){$inc['type_name']=$it['name'];$inc['icon']=$it['icon'];$inc['severity']=$it['severity'];break;}}
    }unset($inc);
    $openCount=count(array_filter($incidents,function($i){return ($i['status']??'')==='open';}));
    $criticalCount=count(array_filter($incidents,function($i){return ($i['severity']??'')==='critical'&&($i['status']??'')==='open';}));
    cir_ok('OK',['incidents'=>$incidents,'types'=>$INCIDENT_TYPES,'count'=>count($incidents),'open'=>$openCount,'critical'=>$criticalCount]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cir_ok('Missing');
    $key='conv_incidents_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $incidents=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='report'){
        $type=$input['type']??'other';
        $orderCode=trim($input['order_code']??'');
        $description=trim($input['description']??'');
        $lat=floatval($input['lat']??0);$lng=floatval($input['lng']??0);
        if(!$description) cir_ok('Mo ta su co');
        $maxId=0;foreach($incidents as $i){if(intval($i['id']??0)>$maxId)$maxId=intval($i['id']);}
        $incidents[]=['id'=>$maxId+1,'type'=>$type,'order_code'=>$orderCode,'description'=>$description,'lat'=>$lat,'lng'=>$lng,'reporter_id'=>$uid,'status'=>'open','resolution'=>'','created_at'=>date('c')];
    }

    if($action==='resolve'){
        $incId=intval($input['incident_id']??0);
        $resolution=trim($input['resolution']??'');
        foreach($incidents as &$i){if(intval($i['id']??0)===$incId){$i['status']='resolved';$i['resolution']=$resolution;$i['resolved_at']=date('c');$i['resolved_by']=$uid;}}unset($i);
    }

    if(count($incidents)>100) $incidents=array_slice($incidents,-100);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($incidents)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($incidents))]);
    cir_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
