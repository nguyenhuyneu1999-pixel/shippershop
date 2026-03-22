<?php
// ShipperShop API v2 — Safety SOS
// Emergency SOS system: alert contacts, share location, record incident
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

$SOS_TYPES=[
    ['id'=>'accident','name'=>'Tai nan','icon'=>'🚨','severity'=>'critical'],
    ['id'=>'robbery','name'=>'Cuop giat','icon'=>'🔴','severity'=>'critical'],
    ['id'=>'breakdown','name'=>'Hong xe','icon'=>'🔧','severity'=>'medium'],
    ['id'=>'lost','name'=>'Lac duong','icon'=>'📍','severity'=>'low'],
    ['id'=>'health','name'=>'Suc khoe','icon'=>'🏥','severity'=>'high'],
    ['id'=>'harassment','name'=>'Quay roi','icon'=>'⚠️','severity'=>'high'],
    ['id'=>'other','name'=>'Khac','icon'=>'🆘','severity'=>'medium'],
];

function sos_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    if($action==='contacts'){
        $key='sos_contacts_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $contacts=$row?json_decode($row['value'],true):[];
        sos_ok('OK',['contacts'=>$contacts,'types'=>$SOS_TYPES]);
    }
    if($action==='history'){
        $key='sos_history_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $history=$row?json_decode($row['value'],true):[];
        sos_ok('OK',['history'=>$history,'count'=>count($history)]);
    }
    sos_ok('OK',['types'=>$SOS_TYPES]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    if($action==='set_contacts'){
        $contacts=$input['contacts']??[];
        $key='sos_contacts_'.$uid;
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($contacts),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($contacts)]);
        sos_ok('Da luu lien he khan cap!');
    }

    if(!$action||$action==='alert'){
        $type=$input['type']??'other';
        $lat=floatval($input['lat']??0);
        $lng=floatval($input['lng']??0);
        $message=trim($input['message']??'');
        $key='sos_history_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $history=$row?json_decode($row['value'],true):[];
        $maxId=0;foreach($history as $h){if(intval($h['id']??0)>$maxId)$maxId=intval($h['id']);}
        $alert=['id'=>$maxId+1,'type'=>$type,'lat'=>$lat,'lng'=>$lng,'message'=>$message,'status'=>'active','created_at'=>date('c')];
        array_unshift($history,$alert);
        if(count($history)>50) $history=array_slice($history,0,50);
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($history),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($history)]);
        // Log to audit
        $d->query("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,?,?,?,NOW())",[$uid,'sos_alert',json_encode($alert),$_SERVER['REMOTE_ADDR']??'']);
        sos_ok('SOS da gui! Giu binh tinh.');
    }
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
