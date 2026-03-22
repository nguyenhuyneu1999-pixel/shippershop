<?php
// ShipperShop API v2 — Tip Jar
// Receive and track tips from customers
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function tj_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {
$uid=require_auth();
$key='tip_jar_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tips=$row?json_decode($row['value'],true):[];
    $total=0;$today=0;$month=0;
    foreach($tips as $t){
        $amt=intval($t['amount']??0);$total+=$amt;
        if(substr($t['date']??'',0,10)===date('Y-m-d')) $today+=$amt;
        if(substr($t['date']??'',0,7)===date('Y-m')) $month+=$amt;
    }
    tj_ok('OK',['tips'=>array_slice($tips,0,30),'stats'=>['total'=>$total,'today'=>$today,'month'=>$month,'count'=>count($tips)]]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tips=$row?json_decode($row['value'],true):[];
    $amount=max(1000,intval($input['amount']??0));
    $from=trim($input['from']??'');
    $maxId=0;foreach($tips as $t){if(intval($t['id']??0)>$maxId)$maxId=intval($t['id']);}
    array_unshift($tips,['id'=>$maxId+1,'amount'=>$amount,'from'=>$from,'date'=>date('Y-m-d H:i:s')]);
    if(count($tips)>200) $tips=array_slice($tips,0,200);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($tips),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($tips)]);
    tj_ok('Da ghi tip '.number_format($amount).'d!');
}
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
