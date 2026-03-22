<?php
// ShipperShop API v2 — Conversation Notes
// Private notes on conversations (visible only to note creator)
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

function cn_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Get note for conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cn_ok('OK',['note'=>'']);
    $key='conv_note_'.$uid.'_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    cn_ok('OK',['note'=>$row?$row['value']:'','conversation_id'=>$convId]);
}

// Save note
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $note=trim($input['note']??'');
    if(!$convId) cn_ok('Missing conversation_id');

    $key='conv_note_'.$uid.'_'.$convId;
    if($note){
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[$note,$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,$note]);
        cn_ok('Da luu ghi chu');
    }else{
        $d->query("DELETE FROM settings WHERE `key`=?",[$key]);
        cn_ok('Da xoa ghi chu');
    }
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
