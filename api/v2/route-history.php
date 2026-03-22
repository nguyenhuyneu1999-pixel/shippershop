<?php
// ShipperShop API v2 — Route History
// Tinh nang: Luu va phat lai cac tuyen giao hang
// Save and replay past delivery routes with stats comparison
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

function rh2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='route_history_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $routes=$row?json_decode($row['value'],true):[];
    $totalKm=0;$totalOrders=0;$totalTime=0;
    foreach($routes as $r){$totalKm+=floatval($r['km']??0);$totalOrders+=intval($r['orders']??0);$totalTime+=floatval($r['hours']??0);}
    $avgKmPerRoute=count($routes)>0?round($totalKm/count($routes),1):0;
    $avgOrdersPerRoute=count($routes)>0?round($totalOrders/count($routes),1):0;
    rh2_ok('OK',['routes'=>array_slice($routes,0,30),'stats'=>['total_routes'=>count($routes),'total_km'=>round($totalKm,1),'total_orders'=>$totalOrders,'total_hours'=>round($totalTime,1),'avg_km'=>$avgKmPerRoute,'avg_orders'=>$avgOrdersPerRoute]]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $routes=$row?json_decode($row['value'],true):[];
    $name=trim($input['name']??'');
    $startArea=trim($input['start_area']??'');$endArea=trim($input['end_area']??'');
    $km=max(0.1,floatval($input['km']??0));$orders=intval($input['orders']??0);
    $hours=max(0.5,floatval($input['hours']??0));$stops=$input['stops']??[];
    if(!$name) rh2_ok('Nhap ten tuyen');
    $maxId=0;foreach($routes as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
    $efficiency=$hours>0?round($orders/$hours,1):0;
    array_unshift($routes,['id'=>$maxId+1,'name'=>$name,'date'=>date('Y-m-d'),'start_area'=>$startArea,'end_area'=>$endArea,'km'=>$km,'orders'=>$orders,'hours'=>$hours,'stops'=>$stops,'efficiency'=>$efficiency,'created_at'=>date('c')]);
    if(count($routes)>100) $routes=array_slice($routes,0,100);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($routes)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($routes))]);
    rh2_ok('Da luu tuyen! Hieu suat: '.$efficiency.' don/h');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
