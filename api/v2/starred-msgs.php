<?php
// ShipperShop API v2 — Starred Messages
// Star/unstar important messages for quick access
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

function sm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='starred_msgs_'.$uid;

// GET: list starred messages
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $starred=$row?json_decode($row['value'],true):[];
    if($convId) $starred=array_values(array_filter($starred,function($s) use($convId){return intval($s['conv_id']??0)===$convId;}));
    // Enrich with message content
    $result=[];
    foreach(array_slice($starred,0,50) as $s){
        $msg=$d->fetchOne("SELECT m.id,m.content,m.created_at,u.fullname FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=?",[intval($s['msg_id']??0)]);
        if($msg){$msg['starred_at']=$s['at']??'';$result[]=$msg;}
    }
    sm_ok('OK',['messages'=>$result,'count'=>count($result)]);
}

// POST: toggle star
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $msgId=intval($input['message_id']??0);
    $convId=intval($input['conversation_id']??0);
    if(!$msgId) sm_ok('Missing message_id');

    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $starred=$row?json_decode($row['value'],true):[];

    $found=false;
    foreach($starred as $i=>$s){
        if(intval($s['msg_id']??0)===$msgId){array_splice($starred,$i,1);$found=true;break;}
    }
    if(!$found){
        array_unshift($starred,['msg_id'=>$msgId,'conv_id'=>$convId,'at'=>date('c')]);
        if(count($starred)>200) $starred=array_slice($starred,0,200);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($starred)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($starred))]);

    sm_ok($found?'Da bo danh dau':'Da danh dau sao!',['starred'=>!$found]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
