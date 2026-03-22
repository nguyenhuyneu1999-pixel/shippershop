<?php
// ShipperShop API v2 — COD Tracker
// Track Cash-on-Delivery collections: pending, collected, deposited
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
    $stats=['total_collected'=>0,'total_deposited'=>0,'pending'=>0,'today_collected'=>0,'entries'=>0];
    foreach($entries as $e){
        $stats['entries']++;
        $amt=intval($e['amount']??0);
        if(($e['status']??'')==='collected'){$stats['total_collected']+=$amt;$stats['pending']+=$amt;}
        if(($e['status']??'')==='deposited'){$stats['total_collected']+=$amt;$stats['total_deposited']+=$amt;}
        if(($e['status']??'')==='collected'&&substr($e['created_at']??'',0,10)===date('Y-m-d')) $stats['today_collected']+=$amt;
    }
    cod_ok('OK',['entries'=>array_slice($entries,0,50),'stats'=>$stats]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='collect'){
        $orderId=trim($input['order_id']??'');
        $amount=intval($input['amount']??0);
        $recipient=trim($input['recipient']??'');
        $company=trim($input['company']??'');
        if($amount<=0) cod_ok('Nhap so tien COD');
        $maxId=0;foreach($entries as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        array_unshift($entries,['id'=>$maxId+1,'order_id'=>$orderId,'amount'=>$amount,'recipient'=>$recipient,'company'=>$company,'status'=>'collected','created_at'=>date('c')]);
        if(count($entries)>500) $entries=array_slice($entries,0,500);
    }

    if($action==='deposit'){
        $entryId=intval($input['entry_id']??0);
        foreach($entries as &$e){if(intval($e['id']??0)===$entryId&&$e['status']==='collected'){$e['status']='deposited';$e['deposited_at']=date('c');}}unset($e);
    }

    if($action==='deposit_all'){
        foreach($entries as &$e){if($e['status']==='collected'){$e['status']='deposited';$e['deposited_at']=date('c');}}unset($e);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($entries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($entries))]);
    cod_ok($action==='deposit_all'?'Da nop tat ca COD!':'OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
