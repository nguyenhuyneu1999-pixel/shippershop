<?php
// ShipperShop API v2 — Delivery Receipt
// Generate delivery confirmation receipts in conversations
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

function dr2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) dr2_ok('OK',['receipts'=>[]]);
    $key='delivery_receipts_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $receipts=$row?json_decode($row['value'],true):[];
    foreach($receipts as &$r){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($r['shipper_id']??0)]);
        if($u) $r['shipper_name']=$u['fullname'];
    }unset($r);
    dr2_ok('OK',['receipts'=>$receipts,'count'=>count($receipts)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) dr2_ok('Missing conversation_id');

    $receipt=['shipper_id'=>$uid,'recipient'=>trim($input['recipient']??''),'address'=>trim($input['address']??''),'items'=>trim($input['items']??''),'cod_amount'=>intval($input['cod_amount']??0),'cod_collected'=>!empty($input['cod_collected']),'delivery_time'=>date('c'),'signature'=>!empty($input['signature']),'note'=>trim($input['note']??''),'receipt_no'=>'RC-'.strtoupper(substr(md5(time()),0,6))];

    $key='delivery_receipts_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $receipts=$row?json_decode($row['value'],true):[];
    array_unshift($receipts,$receipt);
    if(count($receipts)>100) $receipts=array_slice($receipts,0,100);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($receipts),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($receipts)]);
    dr2_ok('Da tao bien lai! Ma: '.$receipt['receipt_no']);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
