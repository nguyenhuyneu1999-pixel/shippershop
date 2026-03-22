<?php
// ShipperShop API v2 — Mileage Log
// Track daily km driven, odometer readings, cost per km
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

function ml_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='mileage_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];
    $todayKm=0;$weekKm=0;$monthKm=0;$totalKm=0;
    $today=date('Y-m-d');$weekStart=date('Y-m-d',strtotime('monday this week'));$monthStart=date('Y-m-01');
    foreach($entries as $e){
        $km=floatval($e['km']??0);$totalKm+=$km;
        if(($e['date']??'')===$today) $todayKm+=$km;
        if(($e['date']??'')>=$weekStart) $weekKm+=$km;
        if(($e['date']??'')>=$monthStart) $monthKm+=$km;
    }
    // Cost per km (fuel entries)
    $fuelKey='fuel_'.$uid;
    $fRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$fuelKey]);
    $fuelEntries=$fRow?json_decode($fRow['value'],true):[];
    $monthFuel=0;foreach($fuelEntries as $f){if(($f['date']??'')>=$monthStart) $monthFuel+=intval($f['cost']??0);}
    $costPerKm=$monthKm>0?round($monthFuel/$monthKm):0;

    ml_ok('OK',['entries'=>array_slice($entries,0,30),'stats'=>['today'=>round($todayKm,1),'week'=>round($weekKm,1),'month'=>round($monthKm,1),'total'=>round($totalKm,1),'cost_per_km'=>$costPerKm,'fuel_month'=>$monthFuel]]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];
    $km=max(0.1,min(500,floatval($input['km']??0)));
    $odometer=intval($input['odometer']??0);
    $note=trim($input['note']??'');
    $maxId=0;foreach($entries as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
    array_unshift($entries,['id'=>$maxId+1,'date'=>date('Y-m-d'),'km'=>$km,'odometer'=>$odometer,'note'=>$note,'created_at'=>date('c')]);
    if(count($entries)>365) $entries=array_slice($entries,0,365);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($entries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($entries))]);
    ml_ok('Da ghi '.$km.' km!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
