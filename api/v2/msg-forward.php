<?php
// ShipperShop API v2 — Message Forwarding
// Forward a message to another conversation or user
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

function mf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function mf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='POST'){
    rate_enforce('msg_forward',20,3600);
    $input=json_decode(file_get_contents('php://input'),true);
    $msgId=intval($input['message_id']??0);
    $targetConvId=intval($input['conversation_id']??0);
    $targetUserId=intval($input['user_id']??0);

    if(!$msgId) mf_fail('Missing message_id');
    if(!$targetConvId&&!$targetUserId) mf_fail('Chọn người nhận hoặc cuộc trò chuyện');

    // Get original message
    $msg=$d->fetchOne("SELECT m.*,u.fullname as sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=?",[$msgId]);
    if(!$msg) mf_fail('Tin nhắn không tồn tại',404);

    // Verify sender is member of original conversation
    $member=$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$msg['conversation_id'],$uid]);
    if(!$member) mf_fail('Không có quyền',403);

    // Find or create target conversation
    if($targetUserId&&!$targetConvId){
        // Find existing conversation with this user
        $conv=$d->fetchOne("SELECT cm1.conversation_id FROM conversation_members cm1 JOIN conversation_members cm2 ON cm1.conversation_id=cm2.conversation_id WHERE cm1.user_id=? AND cm2.user_id=? LIMIT 1",[$uid,$targetUserId]);
        if($conv){
            $targetConvId=intval($conv['conversation_id']);
        }else{
            // Create new conversation
            $pdo->prepare("INSERT INTO conversations (`status`,created_at,updated_at) VALUES ('active',NOW(),NOW())")->execute();
            $targetConvId=intval($pdo->lastInsertId());
            if(!$targetConvId){$r=$pdo->query("SELECT MAX(id) as m FROM conversations");$targetConvId=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
            $pdo->prepare("INSERT INTO conversation_members (conversation_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$targetConvId,$uid]);
            $pdo->prepare("INSERT INTO conversation_members (conversation_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$targetConvId,$targetUserId]);
        }
    }

    // Forward message
    $fwdContent='[Chuyển tiếp từ '.$msg['sender_name']."]\n".($msg['content']??'');
    $pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,message_type,created_at) VALUES (?,?,?,'forwarded',NOW())")->execute([$targetConvId,$uid,$fwdContent]);
    $newId=intval($pdo->lastInsertId());

    // Update conversation timestamp
    $d->query("UPDATE conversations SET updated_at=NOW() WHERE id=?",[$targetConvId]);

    mf_ok('Đã chuyển tiếp!',['message_id'=>$newId,'conversation_id'=>$targetConvId]);
}

// GET: list conversations to forward to
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convs=$d->fetchAll("SELECT c.id,c.updated_at,u.fullname,u.avatar FROM conversations c JOIN conversation_members cm1 ON c.id=cm1.conversation_id JOIN conversation_members cm2 ON c.id=cm2.conversation_id JOIN users u ON cm2.user_id=u.id WHERE cm1.user_id=? AND cm2.user_id!=? AND c.`status`='active' ORDER BY c.updated_at DESC LIMIT 20",[$uid,$uid]);
    mf_ok('OK',$convs);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
