<?php
// ShipperShop API v2 — Shipper Income Tracker
// Track daily/weekly/monthly income from deliveries
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

function it_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='income_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];

    // Calculate summaries
    $today=0;$week=0;$month=0;$total=0;
    $now=time();
    foreach($entries as $e){
        $amt=intval($e['amount']??0);
        $ts=strtotime($e['date']??'');
        $total+=$amt;
        if($ts>=strtotime('today')) $today+=$amt;
        if($ts>=strtotime('-7 days')) $week+=$amt;
        if($ts>=strtotime(date('Y-m-01'))) $month+=$amt;
    }

    // Daily average (last 30 entries)
    $recent=array_slice($entries,0,30);
    $avgDaily=count($recent)?round(array_sum(array_column($recent,'amount'))/count($recent)):0;

    it_ok('OK',['entries'=>array_slice($entries,0,50),'summary'=>['today'=>$today,'week'=>$week,'month'=>$month,'total'=>$total,'avg_daily'=>$avgDaily],'count'=>count($entries)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'add';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];

    if($action==='add'){
        $amount=intval($input['amount']??0);
        $note=trim($input['note']??'');
        $date=$input['date']??date('Y-m-d');
        $deliveries=intval($input['deliveries']??0);
        if($amount<=0) it_ok('Nhap so tien');
        $maxId=0;foreach($entries as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        array_unshift($entries,['id'=>$maxId+1,'amount'=>$amount,'note'=>$note,'date'=>$date,'deliveries'=>$deliveries,'created_at'=>date('c')]);
        if(count($entries)>365) $entries=array_slice($entries,0,365);
    }

    if($action==='delete'){
        $entryId=intval($input['entry_id']??0);
        $entries=array_values(array_filter($entries,function($e) use($entryId){return intval($e['id']??0)!==$entryId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($entries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($entries))]);
    it_ok($action==='delete'?'Da xoa':'Da them thu nhap!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
