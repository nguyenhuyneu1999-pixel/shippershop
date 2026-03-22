<?php
// ShipperShop API v2 — Message Polling (lightweight check for new messages)
// Returns new messages since last check, minimal payload
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-cache');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function mp_ok($data){echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$since=$_GET['since']??null; // ISO datetime
$convId=intval($_GET['conversation_id']??0);

// Get new messages since timestamp
$w=["m.sender_id!=?"];$p=[$uid];

if($since){
    $w[]="m.created_at > ?";
    $p[]=$since;
}

if($convId){
    $w[]="m.conversation_id=?";
    $p[]=$convId;
}else{
    // All conversations user is member of
    $w[]="m.conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?)";
    $p[]=$uid;
}

$wc=implode(' AND ',$w);
$messages=$d->fetchAll("SELECT m.id,m.conversation_id,m.sender_id,m.content,m.message_type,m.created_at,u.fullname as sender_name,u.avatar as sender_avatar FROM messages m JOIN users u ON m.sender_id=u.id WHERE $wc ORDER BY m.created_at ASC LIMIT 50",$p);

// Unread counts per conversation
$unread=$d->fetchAll("SELECT conversation_id,COUNT(*) as c FROM messages WHERE conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?) AND sender_id!=? AND is_read=0 GROUP BY conversation_id",[$uid,$uid]);
$unreadMap=[];$totalUnread=0;
foreach($unread as $u){$unreadMap[intval($u['conversation_id'])]=intval($u['c']);$totalUnread+=intval($u['c']);}

// Typing indicators
$typers=[];
if($convId){
    $typers=$d->fetchAll("SELECT u.id,u.fullname FROM typing_indicators ti JOIN users u ON ti.user_id=u.id WHERE ti.conversation_id=? AND ti.user_id!=? AND ti.started_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)",[$convId,$uid]);
}

mp_ok([
    'messages'=>$messages,
    'unread_total'=>$totalUnread,
    'unread_per_conv'=>$unreadMap,
    'typing'=>$typers,
    'server_time'=>date('c'),
]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
