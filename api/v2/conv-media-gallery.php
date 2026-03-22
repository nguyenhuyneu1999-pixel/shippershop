<?php
// ShipperShop API v2 — Conversation Media Gallery
// Browse all shared images/files in a conversation
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

function cmg_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$convId=intval($_GET['conversation_id']??0);
if(!$convId) cmg_ok('OK',['media'=>[]]);

// Get messages with attachments
$messages=$d->fetchAll("SELECT id,sender_id,content,attachment,created_at FROM messages WHERE conversation_id=? AND attachment IS NOT NULL AND attachment!='' ORDER BY created_at DESC LIMIT 50",[$convId]);

$media=[];
foreach($messages as $m){
    $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($m['sender_id'])]);
    $ext=strtolower(pathinfo($m['attachment'],PATHINFO_EXTENSION));
    $type=in_array($ext,['jpg','jpeg','png','gif','webp'])?'image':(in_array($ext,['mp4','mov','avi'])?'video':'file');
    $media[]=['message_id'=>intval($m['id']),'sender_name'=>$u?$u['fullname']:'','attachment'=>$m['attachment'],'type'=>$type,'ext'=>$ext,'content'=>mb_substr($m['content']??'',0,50),'created_at'=>$m['created_at']];
}

$imageCount=count(array_filter($media,function($m){return $m['type']==='image';}));
$videoCount=count(array_filter($media,function($m){return $m['type']==='video';}));
$fileCount=count(array_filter($media,function($m){return $m['type']==='file';}));

cmg_ok('OK',['media'=>$media,'stats'=>['images'=>$imageCount,'videos'=>$videoCount,'files'=>$fileCount,'total'=>count($media)]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
