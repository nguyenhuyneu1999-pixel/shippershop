<?php
// ShipperShop API v2 — Conversation Weather Share
// Share current weather conditions in conversation for delivery planning
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

$CONDITIONS=[
    ['id'=>'sunny','name'=>'Nang','icon'=>'☀️','risk'=>'low','tip'=>'Thoi tiet ly tuong de giao hang'],
    ['id'=>'cloudy','name'=>'May','icon'=>'☁️','risk'=>'low','tip'=>'Thoi tiet tot, it nang'],
    ['id'=>'light_rain','name'=>'Mua nho','icon'=>'🌦️','risk'=>'medium','tip'=>'Mang ao mua, can than duong tron'],
    ['id'=>'heavy_rain','name'=>'Mua to','icon'=>'🌧️','risk'=>'high','tip'=>'Han che di, chay cham, che hang'],
    ['id'=>'storm','name'=>'Bao','icon'=>'⛈️','risk'=>'critical','tip'=>'KHONG NEN DI! Tim cho tru'],
    ['id'=>'hot','name'=>'Nong','icon'=>'🔥','risk'=>'medium','tip'=>'Uong nuoc, tranh nang trua'],
    ['id'=>'foggy','name'=>'Suong mu','icon'=>'🌫️','risk'=>'medium','tip'=>'Bat den, chay cham, can than'],
    ['id'=>'windy','name'=>'Gio manh','icon'=>'💨','risk'=>'medium','tip'=>'Giu chat hang, can than bao hieu'],
];

function cws_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cws_ok('OK',['shares'=>[],'conditions'=>$CONDITIONS]);
    $key='weather_share_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $shares=$row?json_decode($row['value'],true):[];
    foreach($shares as &$s){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($s['user_id']??0)]);
        if($u) $s['user_name']=$u['fullname'];
    }unset($s);
    cws_ok('OK',['shares'=>$shares,'conditions'=>$CONDITIONS,'count'=>count($shares)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $conditionId=trim($input['condition']??'');
    $location=trim($input['location']??'');
    if(!$convId||!$conditionId) cws_ok('Missing data');
    $key='weather_share_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $shares=$row?json_decode($row['value'],true):[];
    $maxId=0;foreach($shares as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
    array_unshift($shares,['id'=>$maxId+1,'condition'=>$conditionId,'location'=>$location,'user_id'=>$uid,'created_at'=>date('c')]);
    if(count($shares)>50) $shares=array_slice($shares,0,50);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($shares),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($shares)]);
    cws_ok('Da chia se thoi tiet!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
