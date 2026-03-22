<?php
// ShipperShop API v2 — User Availability Status
// Shippers set their availability: available, busy, offline, on delivery
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
$STATUSES=[
    ['id'=>'available','name'=>'San sang','icon'=>'🟢','color'=>'#22c55e'],
    ['id'=>'busy','name'=>'Ban','icon'=>'🟡','color'=>'#f59e0b'],
    ['id'=>'on_delivery','name'=>'Dang giao','icon'=>'🚛','color'=>'#3b82f6'],
    ['id'=>'offline','name'=>'Nghi','icon'=>'⚫','color'=>'#6b7280'],
];

function ua_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// GET: user's status or available statuses
if($_SERVER['REQUEST_METHOD']==='GET'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) ua_ok('OK',['statuses'=>$STATUSES]);
    $key='availability_'.$userId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $status=$row?json_decode($row['value'],true):['status'=>'offline','message'=>'','updated_at'=>''];
    ua_ok('OK',['current'=>$status,'statuses'=>$STATUSES]);
}

// POST: set status
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $statusId=trim($input['status']??'available');
    $message=trim($input['message']??'');
    $valid=array_column($STATUSES,'id');
    if(!in_array($statusId,$valid)) $statusId='available';

    $data=['status'=>$statusId,'message'=>$message,'updated_at'=>date('c')];
    $key='availability_'.$uid;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($data),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($data)]);

    ua_ok('Da cap nhat!',$data);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
