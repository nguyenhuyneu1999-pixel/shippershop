<?php
// ShipperShop API v2 — Fuel Tracker
// Track fuel expenses, mileage, cost per delivery
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

function ft_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='fuel_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];
    $totalCost=0;$totalLiters=0;$totalKm=0;
    foreach($entries as $e){$totalCost+=intval($e['cost']??0);$totalLiters+=floatval($e['liters']??0);$totalKm+=floatval($e['km']??0);}
    $costPerKm=$totalKm>0?round($totalCost/$totalKm):0;
    $kmPerLiter=$totalLiters>0?round($totalKm/$totalLiters,1):0;
    $monthCost=0;
    foreach($entries as $e){if(strtotime($e['date']??'')>=strtotime(date('Y-m-01')))$monthCost+=intval($e['cost']??0);}

    ft_ok('OK',['entries'=>array_slice($entries,0,50),'summary'=>['total_cost'=>$totalCost,'total_liters'=>$totalLiters,'total_km'=>$totalKm,'cost_per_km'=>$costPerKm,'km_per_liter'=>$kmPerLiter,'month_cost'=>$monthCost],'count'=>count($entries)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'add';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];

    if($action==='add'){
        $cost=intval($input['cost']??0);
        $liters=floatval($input['liters']??0);
        $km=floatval($input['km']??0);
        $station=trim($input['station']??'');
        $date=$input['date']??date('Y-m-d');
        if($cost<=0) ft_ok('Nhap so tien');
        $maxId=0;foreach($entries as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        array_unshift($entries,['id'=>$maxId+1,'cost'=>$cost,'liters'=>$liters,'km'=>$km,'station'=>$station,'date'=>$date,'created_at'=>date('c')]);
        if(count($entries)>365) $entries=array_slice($entries,0,365);
    }
    if($action==='delete'){
        $entryId=intval($input['entry_id']??0);
        $entries=array_values(array_filter($entries,function($e) use($entryId){return intval($e['id']??0)!==$entryId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($entries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($entries))]);
    ft_ok($action==='delete'?'Da xoa':'Da them chi phi xang!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
