<?php
// ShipperShop API v2 — Conversation Bookmarks
// Bookmark important messages in conversations
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

function cb_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='msg_bookmarks_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $bookmarks=$row?json_decode($row['value'],true):[];
    // Enrich with message content
    foreach($bookmarks as &$b){
        if($msgId=intval($b['message_id']??0)){
            $msg=$d->fetchOne("SELECT m.content,m.created_at,u.fullname FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=?",[$msgId]);
            if($msg){$b['content']=$msg['content'];$b['sender']=$msg['fullname'];$b['msg_date']=$msg['created_at'];}
        }
    }unset($b);
    cb_ok('OK',['bookmarks'=>$bookmarks,'count'=>count($bookmarks)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'add';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $bookmarks=$row?json_decode($row['value'],true):[];

    if($action==='add'){
        $msgId=intval($input['message_id']??0);
        $convId=intval($input['conversation_id']??0);
        $note=trim($input['note']??'');
        if(!$msgId) cb_ok('Missing message_id');
        // Check not already bookmarked
        foreach($bookmarks as $b){if(intval($b['message_id']??0)===$msgId) cb_ok('Da luu truoc do');}
        $maxId=0;foreach($bookmarks as $b){if(intval($b['id']??0)>$maxId)$maxId=intval($b['id']);}
        $bookmarks[]=['id'=>$maxId+1,'message_id'=>$msgId,'conversation_id'=>$convId,'note'=>$note,'created_at'=>date('c')];
        if(count($bookmarks)>100) cb_ok('Toi da 100 bookmark');
    }

    if($action==='remove'){
        $bmId=intval($input['bookmark_id']??0);
        $bookmarks=array_values(array_filter($bookmarks,function($b) use($bmId){return intval($b['id']??0)!==$bmId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($bookmarks)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($bookmarks))]);
    cb_ok($action==='remove'?'Da xoa':'Da luu bookmark!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
