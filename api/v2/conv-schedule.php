<?php
// ShipperShop API v2 — Conversation Schedule
// Schedule messages to be sent at specific times
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

function cs2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='scheduled_msgs_'.$uid;

// List scheduled messages
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $msgs=$row?json_decode($row['value'],true):[];
    // Enrich with conversation info
    foreach($msgs as &$m){
        if($cid=intval($m['conv_id']??0)){
            $other=$d->fetchOne("SELECT u.fullname FROM conversation_members cm JOIN users u ON cm.user_id=u.id WHERE cm.conversation_id=? AND cm.user_id!=? LIMIT 1",[$cid,$uid]);
            if($other) $m['to']=$other['fullname'];
        }
        $m['is_due']=isset($m['send_at'])&&strtotime($m['send_at'])<=time();
    }unset($m);
    cs2_ok('OK',['messages'=>$msgs,'count'=>count($msgs),'due'=>count(array_filter($msgs,function($m){return $m['is_due']??false;}))]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $msgs=$row?json_decode($row['value'],true):[];

    // Schedule message
    if(!$action||$action==='schedule'){
        $convId=intval($input['conversation_id']??0);
        $content=trim($input['content']??'');
        $sendAt=$input['send_at']??'';
        if(!$convId||!$content||!$sendAt) cs2_ok('Thieu du lieu');
        $maxId=0;foreach($msgs as $m){if(intval($m['id']??0)>$maxId)$maxId=intval($m['id']);}
        $msgs[]=['id'=>$maxId+1,'conv_id'=>$convId,'content'=>$content,'send_at'=>$sendAt,'status'=>'pending','created_at'=>date('c')];
        if(count($msgs)>30) cs2_ok('Toi da 30 tin nhan hen gio');
    }

    // Cancel
    if($action==='cancel'){
        $msgId=intval($input['message_id']??0);
        $msgs=array_values(array_filter($msgs,function($m) use($msgId){return intval($m['id']??0)!==$msgId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($msgs)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($msgs))]);

    cs2_ok($action==='cancel'?'Da huy':'Da hen gio gui!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
