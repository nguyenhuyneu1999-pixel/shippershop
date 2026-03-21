<?php
// ShipperShop API v2 — Group Chat
// Real-time messaging within groups
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
$action=$_GET['action']??'';

function gc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function gc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get group messages
    if(!$action||$action==='messages'){
        $groupId=intval($_GET['group_id']??0);
        if(!$groupId) gc_fail('Missing group_id');
        // Check membership
        $member=$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$groupId,$uid]);
        if(!$member) gc_fail('Bạn chưa tham gia nhóm này',403);

        $page=max(1,intval($_GET['page']??1));$limit=30;$offset=($page-1)*$limit;
        $since=$_GET['since']??null;

        $w="m.group_id=?";$p=[$groupId];
        if($since){$w.=" AND m.created_at > ?";$p[]=$since;}

        $messages=$d->fetchAll("SELECT m.*,u.fullname as sender_name,u.avatar as sender_avatar,u.shipping_company FROM group_messages m JOIN users u ON m.sender_id=u.id WHERE $w ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset",$p);
        // Reverse for display
        $messages=array_reverse($messages);
        gc_ok('OK',['messages'=>$messages,'group_id'=>$groupId]);
    }

    // Poll for new messages
    if($action==='poll'){
        $groupId=intval($_GET['group_id']??0);
        $since=$_GET['since']??date('c',time()-30);
        if(!$groupId) gc_fail('Missing group_id');
        $messages=$d->fetchAll("SELECT m.*,u.fullname as sender_name,u.avatar as sender_avatar FROM group_messages m JOIN users u ON m.sender_id=u.id WHERE m.group_id=? AND m.sender_id!=? AND m.created_at > ? ORDER BY m.created_at ASC LIMIT 20",[$groupId,$uid,$since]);
        gc_ok('OK',['messages'=>$messages,'server_time'=>date('c')]);
    }

    gc_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Send message to group
    if(!$action||$action==='send'){
        rate_enforce('group_msg',30,60);
        $groupId=intval($input['group_id']??0);
        $content=trim($input['content']??'');
        if(!$groupId) gc_fail('Missing group_id');
        if(!$content||mb_strlen($content)<1) gc_fail('Tin nhắn trống');
        if(mb_strlen($content)>2000) gc_fail('Tin nhắn tối đa 2000 ký tự');

        // Check membership
        $member=$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$groupId,$uid]);
        if(!$member) gc_fail('Bạn chưa tham gia nhóm này',403);

        // Check if table exists, create if not
        try{
            $pdo->prepare("INSERT INTO group_messages (group_id,sender_id,content,created_at) VALUES (?,?,?,NOW())")->execute([$groupId,$uid,$content]);
        }catch(\Throwable $e){
            // Table might not exist — create it
            $pdo->exec("CREATE TABLE IF NOT EXISTS group_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                group_id INT NOT NULL,
                sender_id INT NOT NULL,
                content TEXT,
                image_url VARCHAR(500) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_group (group_id),
                INDEX idx_sender (sender_id)
            )");
            $pdo->prepare("INSERT INTO group_messages (group_id,sender_id,content,created_at) VALUES (?,?,?,NOW())")->execute([$groupId,$uid,$content]);
        }
        $id=intval($pdo->lastInsertId());
        if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM group_messages");$id=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

        gc_ok('Đã gửi',['id'=>$id,'group_id'=>$groupId,'content'=>$content,'created_at'=>date('c')]);
    }

    // Delete message (own or admin)
    if($action==='delete'){
        $msgId=intval($input['message_id']??0);
        if(!$msgId) gc_fail('Missing message_id');
        $msg=$d->fetchOne("SELECT sender_id,group_id FROM group_messages WHERE id=?",[$msgId]);
        if(!$msg) gc_fail('Tin nhắn không tồn tại',404);
        // Check if owner or group admin
        $isOwner=intval($msg['sender_id'])===$uid;
        $isAdmin=!!$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=? AND role='admin'",[$msg['group_id'],$uid]);
        if(!$isOwner&&!$isAdmin) gc_fail('Không có quyền',403);
        $d->query("DELETE FROM group_messages WHERE id=?",[$msgId]);
        gc_ok('Đã xóa');
    }

    gc_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
