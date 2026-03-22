<?php
// ShipperShop API v2 — Conversation Read Status
// Track detailed read status per message per user
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

function crs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: read status for conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) crs_ok('OK',['unread'=>0]);
    $unread=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND sender_id!=? AND is_read=0",[$convId,$uid])['c']);
    $lastRead=$d->fetchOne("SELECT MAX(id) as last_id FROM messages WHERE conversation_id=? AND sender_id!=? AND is_read=1",[$convId,$uid]);
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=?",[$convId])['c']);
    crs_ok('OK',['unread'=>$unread,'total'=>$total,'last_read_id'=>intval($lastRead['last_id']??0)]);
}

// POST: mark as read
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $upToId=intval($input['up_to_message_id']??0);
    if(!$convId) crs_ok('Missing conversation_id');

    if($upToId){
        $d->query("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=? AND id<=? AND is_read=0",[$convId,$uid,$upToId]);
    }else{
        $d->query("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=? AND is_read=0",[$convId,$uid]);
    }
    $marked=intval($d->fetchOne("SELECT ROW_COUNT() as c")['c']??0);
    crs_ok('Da doc',['marked'=>$marked]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
