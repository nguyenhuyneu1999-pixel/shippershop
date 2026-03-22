<?php
// ShipperShop API v2 — User Availability
// Set and check shipper availability schedule for the week
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
function ua2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
try {
$uid=require_auth();$key='availability_'.$uid;
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedule=$row?json_decode($row['value'],true):null;
    if(!$schedule){$days=['T2','T3','T4','T5','T6','T7','CN'];$schedule=[];foreach($days as $day){$schedule[]=['day'=>$day,'available'=>true,'start'=>'07:00','end'=>'21:00','areas'=>[],'max_orders'=>20];}}
    $availableToday=false;$dow=date('N');$dayMap=[1=>'T2',2=>'T3',3=>'T4',4=>'T5',5=>'T6',6=>'T7',7=>'CN'];
    foreach($schedule as $s){if($s['day']===($dayMap[$dow]??'')&&!empty($s['available'])) $availableToday=true;}
    ua2_ok('OK',['schedule'=>$schedule,'available_today'=>$availableToday,'current_day'=>$dayMap[$dow]??'']);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $schedule=$input['schedule']??[];
    if(!is_array($schedule)||count($schedule)!==7) ua2_ok('Nhap lich 7 ngay');
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($schedule),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($schedule)]);
    ua2_ok('Da cap nhat lich!');
}
} catch (\Throwable $e) {echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
