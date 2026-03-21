<?php
// ShipperShop API v2 — Conversation Pin
// Pin/unpin conversations to top of chat list
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

function cp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Get pinned conversations
if($_SERVER['REQUEST_METHOD']==='GET'){
    $pinned=$d->fetchAll("SELECT conversation_id,created_at as pinned_at FROM pinned_messages WHERE pinned_by=? ORDER BY created_at DESC",[$uid]);
    cp_ok('OK',$pinned);
}

// Toggle pin
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cp_ok('Missing conversation_id');

    $existing=$d->fetchOne("SELECT id FROM pinned_messages WHERE conversation_id=? AND pinned_by=?",[$convId,$uid]);
    if($existing){
        $d->query("DELETE FROM pinned_messages WHERE conversation_id=? AND pinned_by=?",[$convId,$uid]);
        cp_ok('Đã bỏ ghim',['pinned'=>false]);
    }else{
        $count=intval($d->fetchOne("SELECT COUNT(*) as c FROM pinned_messages WHERE pinned_by=?",[$uid])['c']);
        if($count>=5) cp_ok('Tối đa 5 cuộc trò chuyện ghim');
        $pdo->prepare("INSERT INTO pinned_messages (conversation_id,message_id,pinned_by,created_at) VALUES (?,0,?,NOW())")->execute([$convId,$uid]);
        cp_ok('Đã ghim!',['pinned'=>true]);
    }
}

cp_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
