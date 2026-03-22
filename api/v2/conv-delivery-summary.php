<?php
// ShipperShop API v2 — Conversation Delivery Summary
// End-of-day delivery summary: total orders, COD collected, routes, time spent
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

function cdsm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cdsm_ok('OK',['summaries'=>[]]);
    $key='conv_del_summary_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $summaries=$row?json_decode($row['value'],true):[];
    foreach($summaries as &$s){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($s['shipper_id']??0)]);
        if($u) $s['shipper_name']=$u['fullname'];
    }unset($s);
    cdsm_ok('OK',['summaries'=>$summaries,'count'=>count($summaries)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cdsm_ok('Missing conversation_id');
    $key='conv_del_summary_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $summaries=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $totalOrders=intval($input['total_orders']??0);
        $completedOrders=intval($input['completed_orders']??0);
        $failedOrders=intval($input['failed_orders']??0);
        $codCollected=intval($input['cod_collected']??0);
        $kmDriven=floatval($input['km_driven']??0);
        $hoursWorked=floatval($input['hours_worked']??0);
        $fuelCost=intval($input['fuel_cost']??0);
        $income=intval($input['income']??0);
        $areas=$input['areas']??[];
        $notes=trim($input['notes']??'');

        $successRate=$totalOrders>0?round($completedOrders/$totalOrders*100,1):0;
        $profitPerOrder=$completedOrders>0?round(($income-$fuelCost)/$completedOrders):0;
        $kmPerOrder=$completedOrders>0?round($kmDriven/$completedOrders,1):0;

        $maxId=0;foreach($summaries as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        array_unshift($summaries,['id'=>$maxId+1,'date'=>date('Y-m-d'),'shipper_id'=>$uid,'total_orders'=>$totalOrders,'completed'=>$completedOrders,'failed'=>$failedOrders,'success_rate'=>$successRate,'cod_collected'=>$codCollected,'km_driven'=>$kmDriven,'hours_worked'=>$hoursWorked,'fuel_cost'=>$fuelCost,'income'=>$income,'profit_per_order'=>$profitPerOrder,'km_per_order'=>$kmPerOrder,'areas'=>$areas,'notes'=>$notes,'created_at'=>date('c')]);
        if(count($summaries)>60) $summaries=array_slice($summaries,0,60);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($summaries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($summaries))]);
    cdsm_ok('Da luu bao cao ngay!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
