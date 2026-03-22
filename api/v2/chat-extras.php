<?php
// ShipperShop API v2 — Chat Extras (Read receipts, Typing indicators)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ch_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ch_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// Mark messages as read
if($action==='mark_read'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) ch_fail('Missing conversation_id');
    // Mark all unread messages in this conversation as read
    $d->query("UPDATE messages SET read_at=NOW(),is_read=1 WHERE conversation_id=? AND sender_id!=? AND read_at IS NULL",[$convId,$uid]);
    ch_ok('Marked read');
}

// Get read status for messages
if($action==='read_status'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) ch_fail('Missing conversation_id');
    // Get last read message ID per user
    $statuses=$d->fetchAll("SELECT m.id as message_id,m.sender_id,m.read_at,m.is_read FROM messages m WHERE m.conversation_id=? ORDER BY m.created_at DESC LIMIT 50",[$convId]);
    ch_ok('OK',$statuses);
}

// Set typing indicator
if($action==='typing'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $isTyping=!empty($input['typing']);
    if(!$convId) ch_fail('Missing conversation_id');

    if($isTyping){
        try{$pdo->prepare("INSERT INTO typing_indicators (conversation_id,user_id,started_at) VALUES (?,?,NOW()) ON DUPLICATE KEY UPDATE started_at=NOW()")->execute([$convId,$uid]);}catch(\Throwable $e){}
    }else{
        $d->query("DELETE FROM typing_indicators WHERE conversation_id=? AND user_id=?",[$convId,$uid]);
    }
    ch_ok('OK');
}

// Check who's typing in a conversation
if($action==='who_typing'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) ch_fail('Missing conversation_id');
    // Only show typing from last 10 seconds
    $typers=$d->fetchAll("SELECT u.id,u.fullname,u.avatar FROM typing_indicators ti JOIN users u ON ti.user_id=u.id WHERE ti.conversation_id=? AND ti.user_id!=? AND ti.started_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)",[$convId,$uid]);
    ch_ok('OK',$typers);
}

// Unread count per conversation
if($action==='unread'){
    $counts=$d->fetchAll("SELECT conversation_id,COUNT(*) as unread FROM messages WHERE conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?) AND sender_id!=? AND is_read=0 GROUP BY conversation_id",[$uid,$uid]);
    $total=0;$perConv=[];
    foreach($counts as $c){$perConv[intval($c['conversation_id'])]=intval($c['unread']);$total+=intval($c['unread']);}
    ch_ok('OK',['total'=>$total,'per_conversation'=>$perConv]);
}

ch_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
