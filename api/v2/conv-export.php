<?php
// ShipperShop API v2 — Conversation Export
// Export conversation messages as JSON/text for user
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function ce_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ce_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$convId=intval($_GET['conversation_id']??0);
if(!$convId) ce_fail('Missing conversation_id');

// Verify membership
$member=$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$convId,$uid]);
if(!$member) ce_fail('Khong co quyen',403);

$format=$_GET['format']??'json'; // json, text
$limit=min(intval($_GET['limit']??500),1000);

$messages=$d->fetchAll("SELECT m.id,m.content,m.message_type,m.created_at,u.fullname FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=? ORDER BY m.created_at ASC LIMIT $limit",[$convId]);

if($format==='text'){
    $text='';
    foreach($messages as $m){
        $text.='['.$m['created_at'].'] '.$m['fullname'].': '.$m['content']."\n";
    }
    ce_ok('OK',['format'=>'text','content'=>$text,'message_count'=>count($messages),'conversation_id'=>$convId]);
}else{
    ce_ok('OK',['format'=>'json','messages'=>$messages,'message_count'=>count($messages),'conversation_id'=>$convId,'exported_at'=>date('c')]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
