<?php
// ShipperShop API v2 — Conversation Receipt Share
// Generate and share delivery receipts in conversation with QR verification
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

function crs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) crs_ok('OK',['receipts'=>[]]);
    $key='conv_receipts_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $receipts=$row?json_decode($row['value'],true):[];
    foreach($receipts as &$r){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($r['shipper_id']??0)]);
        if($u) $r['shipper_name']=$u['fullname'];
    }unset($r);
    $totalCod=array_sum(array_column($receipts,'cod_amount'));
    $totalFee=array_sum(array_column($receipts,'shipping_fee'));
    crs_ok('OK',['receipts'=>$receipts,'count'=>count($receipts),'total_cod'=>$totalCod,'total_fee'=>$totalFee]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) crs_ok('Missing conversation_id');
    $key='conv_receipts_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $receipts=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $orderCode=trim($input['order_code']??'');
        $recipientName=trim($input['recipient_name']??'');
        $recipientPhone=trim($input['recipient_phone']??'');
        $address=trim($input['address']??'');
        $codAmount=intval($input['cod_amount']??0);
        $shippingFee=intval($input['shipping_fee']??0);
        $company=trim($input['company']??'');
        $notes=trim($input['notes']??'');
        if(!$recipientName) crs_ok('Nhap ten nguoi nhan');
        $receiptNo='RC-'.strtoupper(substr(md5(time().rand()),0,8));
        $verifyCode=strtoupper(substr(md5($receiptNo.date('c')),0,6));
        $receipt=['receipt_no'=>$receiptNo,'verify_code'=>$verifyCode,'order_code'=>$orderCode,'shipper_id'=>$uid,'recipient_name'=>$recipientName,'recipient_phone'=>$recipientPhone,'address'=>$address,'cod_amount'=>$codAmount,'shipping_fee'=>$shippingFee,'company'=>$company,'notes'=>$notes,'status'=>'pending','created_at'=>date('c')];
        array_unshift($receipts,$receipt);
        if(count($receipts)>100) $receipts=array_slice($receipts,0,100);
    }

    if($action==='confirm'){
        $receiptNo=trim($input['receipt_no']??'');
        foreach($receipts as &$r){if(($r['receipt_no']??'')===$receiptNo){$r['status']='confirmed';$r['confirmed_at']=date('c');$r['confirmed_by']=$uid;}}unset($r);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($receipts)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($receipts))]);
    crs_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
