<?php
// ShipperShop API v2 — Delivery Proof
// Store delivery proof (photo description, signature, timestamp) in conversations
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

function dp2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) dp2_ok('OK',['proofs'=>[]]);
    $key='delivery_proofs_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $proofs=$row?json_decode($row['value'],true):[];
    foreach($proofs as &$p){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($p['shipper_id']??0)]);
        if($u) $p['shipper_name']=$u['fullname'];
    }unset($p);
    dp2_ok('OK',['proofs'=>$proofs,'count'=>count($proofs)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) dp2_ok('Missing conversation_id');

    $proof=['shipper_id'=>$uid,'order_id'=>trim($input['order_id']??''),'recipient_name'=>trim($input['recipient_name']??''),'photo_desc'=>trim($input['photo_desc']??''),'delivery_method'=>$input['delivery_method']??'hand','has_signature'=>!empty($input['has_signature']),'cod_collected'=>intval($input['cod_collected']??0),'notes'=>trim($input['notes']??''),'proof_no'=>'PF-'.strtoupper(substr(md5(time().rand()),0,6)),'timestamp'=>date('c'),'lat'=>floatval($input['lat']??0),'lng'=>floatval($input['lng']??0)];

    $key='delivery_proofs_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $proofs=$row?json_decode($row['value'],true):[];
    array_unshift($proofs,$proof);
    if(count($proofs)>100) $proofs=array_slice($proofs,0,100);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($proofs),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($proofs)]);
    dp2_ok('Da luu bang chung giao hang! Ma: '.$proof['proof_no']);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
