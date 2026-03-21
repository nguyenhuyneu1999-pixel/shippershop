<?php
// ShipperShop API v2 — Conversation Archive
// Archive/unarchive conversations, list archived
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

function ca_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ca_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// List archived conversations
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['archived_convs_'.$uid]);
    $archived=$row?json_decode($row['value'],true):[];
    ca_ok('OK',['archived_ids'=>$archived,'count'=>count($archived)]);
}

// Toggle archive
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) ca_fail('Missing conversation_id');

    $key='archived_convs_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $archived=$row?json_decode($row['value'],true):[];

    $idx=array_search($convId,$archived);
    if($idx!==false){
        array_splice($archived,$idx,1);
        $msg='Đã bỏ lưu trữ';$isArchived=false;
    }else{
        $archived[]=$convId;
        $msg='Đã lưu trữ';$isArchived=true;
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($archived),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($archived)]);

    ca_ok($msg,['archived'=>$isArchived]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
