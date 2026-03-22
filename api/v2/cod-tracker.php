<?php
// ShipperShop API v2 — COD Tracker
// Track Cash-on-Delivery collections, pending amounts, reconciliation
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

function cod_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='cod_tracker_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];
    $totalCollected=0;$totalPending=0;$totalRemitted=0;$todayCollected=0;
    foreach($entries as $e){
        if(($e['status']??'')==='collected'){$totalCollected+=intval($e['amount']??0);if(($e['date']??'')==date('Y-m-d'))$todayCollected+=intval($e['amount']??0);}
        if(($e['status']??'')==='pending') $totalPending+=intval($e['amount']??0);
        if(($e['status']??'')==='remitted') $totalRemitted+=intval($e['amount']??0);
    }
    cod_ok('OK',['entries'=>array_slice($entries,0,50),'summary'=>['collected'=>$totalCollected,'pending'=>$totalPending,'remitted'=>$totalRemitted,'today'=>$todayCollected,'total_orders'=>count($entries)]]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $amount=intval($input['amount']??0);
        $orderId=trim($input['order_id']??'');
        $customer=trim($input['customer']??'');
        if($amount<=0) cod_ok('Nhap so tien');
        $maxId=0;foreach($entries as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        array_unshift($entries,['id'=>$maxId+1,'amount'=>$amount,'order_id'=>$orderId,'customer'=>$customer,'status'=>'collected','date'=>date('Y-m-d'),'created_at'=>date('c')]);
        if(count($entries)>500) $entries=array_slice($entries,0,500);
    }

    if($action==='remit'){
        $entryId=intval($input['entry_id']??0);
        foreach($entries as &$e){if(intval($e['id']??0)===$entryId&&$e['status']==='collected'){$e['status']='remitted';$e['remitted_at']=date('c');}}unset($e);
    }

    if($action==='remit_all'){
        foreach($entries as &$e){if($e['status']==='collected'){$e['status']='remitted';$e['remitted_at']=date('c');}}unset($e);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($entries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($entries))]);
    cod_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
