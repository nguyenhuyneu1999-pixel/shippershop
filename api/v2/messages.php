<?php
/**
 * ShipperShop API v2 — Messages (Complete Rewrite)
 * CRITICAL: All INSERT via PDO direct. JWT check direct (no getAuthUserId).
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';
require_once __DIR__.'/../../includes/upload-handler.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$pdo=$d->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data]);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// Auth helper — JWT direct, no getAuthUserId (it calls exit uncatchable)
function msgAuth(){
    if(!empty($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    $headers=function_exists('getallheaders')?getallheaders():[];
    $ah=$headers['Authorization']??$headers['authorization']??$_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'';
    if(preg_match('/Bearer\s+(.+)/',$ah,$m)){
        $data=verifyJWT($m[1]);
        if($data&&isset($data['user_id'])){$_SESSION['user_id']=$data['user_id'];return intval($data['user_id']);}
    }
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Vui lòng đăng nhập']);exit;
}

// Check blocked
function isBlocked($uid1,$uid2){
    return !!db()->fetchOne("SELECT id FROM user_blocks WHERE (user_id=? AND blocked_user_id=?) OR (user_id=? AND blocked_user_id=?)",[$uid1,$uid2,$uid2,$uid1]);
}

// ========== UPLOAD MESSAGE (multipart, before JSON parsing) ==========
if($action==='upload_message' && $method==='POST'){
    $uid=msgAuth();
    $oid=intval($_POST['to_user_id']??0);
    $gid=intval($_POST['group_id']??0);
    $msgType=$_POST['type']??'image';
    
    // Handle file
    $fileUrl=null;$fileName=null;$content=null;
    if(!empty($_FILES['file'])){
        $folder='messages';
        $allowed=['image/jpeg','image/png','image/webp','image/gif','video/mp4','video/quicktime','video/webm','application/pdf'];
        $up=handle_upload($_FILES['file'],$folder,['user_id'=>$uid,'max_size'=>20*1024*1024,'allowed_types'=>$allowed,'resize_max'=>1920]);
        if(!$up['success']) fail($up['error']);
        $fileUrl=$up['url'];
        $fileName=$_FILES['file']['name'];
        $content='['.ucfirst($msgType).']';
    }
    if($msgType==='location'){
        $lat=floatval($_POST['lat']??0);$lng=floatval($_POST['lng']??0);
        $content="📍 Vị trí: $lat, $lng";
        $fileUrl="https://maps.google.com/maps?q=$lat,$lng";
    }
    if(!$fileUrl && !$content) fail('No file');

    // Find/create conversation
    $convId=null;
    if($gid){
        $convId=$gid;
    }else if($oid){
        if(isBlocked($uid,$oid)) fail('Không thể gửi tin nhắn');
        $cv=$pdo->prepare("SELECT id FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)");
        $cv->execute([$uid,$oid,$oid,$uid]);
        $row=$cv->fetch(PDO::FETCH_ASSOC);
        if($row){$convId=intval($row['id']);}
        else{
            $ins=$pdo->prepare("INSERT INTO conversations (user1_id,user2_id,last_message,last_message_at,`status`) VALUES (?,?,?,NOW(),'pending')");
            $ins->execute([$uid,$oid,$content??'[File]']);
            $convId=intval($pdo->lastInsertId());
            if(!$convId){$r=$pdo->query("SELECT MAX(id) as m FROM conversations");$convId=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        }
    }
    if(!$convId) fail('Missing conversation');

    $ins=$pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,`type`,file_url,file_name,created_at) VALUES (?,?,?,?,?,?,NOW())");
    $ins->execute([$convId,$uid,$content,$msgType,$fileUrl,$fileName]);
    $pdo->prepare("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?")->execute([$content??'[File]',$convId]);

    ok('OK',['conversation_id'=>$convId,'type'=>$msgType,'file_url'=>$fileUrl]);
}

// ========== GET ==========
if($method==='GET'){
    $uid=msgAuth();
    try{$d->query("UPDATE users SET is_online=1,last_active=NOW() WHERE id=?",[$uid]);}catch(\Throwable $e){}

    // --- Conversations list ---
    if($action==='conversations'){
        $tab=$_GET['tab']??'active';
        $sf=$tab==='pending'?'pending':'active';
        $rows=$d->fetchAll("SELECT id,user1_id,user2_id,last_message,last_message_at,`status` FROM conversations WHERE (user1_id=? OR user2_id=?) AND `status`=? AND (type='private' OR type IS NULL) ORDER BY last_message_at DESC",[$uid,$uid,$sf]);
        $result=[];
        foreach($rows as $c){
            $oid=($c['user1_id']==$uid)?$c['user2_id']:$c['user1_id'];
            if(isBlocked($uid,$oid)) continue;
            $other=$d->fetchOne("SELECT id,fullname,avatar,shipping_company,is_online,last_active FROM users WHERE id=?",[$oid]);
            if(!$other) continue;
            $un=$d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND sender_id!=? AND is_read=0",[$c['id'],$uid]);
            $result[]=['id'=>$c['id'],'user1_id'=>$c['user1_id'],'user2_id'=>$c['user2_id'],'last_message'=>$c['last_message'],'last_message_at'=>$c['last_message_at'],'status'=>$c['status'],
                'other_id'=>intval($oid),'other_name'=>$other['fullname'],'other_avatar'=>$other['avatar'],'other_ship'=>$other['shipping_company'],'other_online'=>$other['is_online'],'other_last_active'=>$other['last_active'],
                'unread_count'=>intval($un['c']??0),'type'=>'private'];
        }
        ok('OK',$result);
    }

    // --- Group conversations ---
    if($action==='group_conversations'){
        $groups=$d->fetchAll("SELECT c.id,c.name,c.avatar,c.last_message,c.last_message_at,c.description,c.invite_link,(SELECT COUNT(*) FROM conversation_members WHERE conversation_id=c.id) as member_count,(SELECT COUNT(*) FROM messages WHERE conversation_id=c.id AND sender_id!=? AND is_read=0) as unread_count FROM conversations c JOIN conversation_members cm ON cm.conversation_id=c.id WHERE cm.user_id=? AND c.type='group' AND c.`status`='active' ORDER BY c.last_message_at DESC",[$uid,$uid]);
        ok('OK',$groups);
    }

    // --- Group info ---
    if($action==='group_info'){
        $gid=intval($_GET['conversation_id']??0);
        $g=$d->fetchOne("SELECT c.*,(SELECT COUNT(*) FROM conversation_members WHERE conversation_id=c.id) as member_count FROM conversations c WHERE c.id=? AND c.type='group'",[$gid]);
        if(!$g) fail('Not found',404);
        ok('OK',$g);
    }

    // --- Group members ---
    if($action==='group_members'){
        $gid=intval($_GET['conversation_id']??0);
        $members=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_online,cm.role FROM conversation_members cm JOIN users u ON cm.user_id=u.id WHERE cm.conversation_id=? ORDER BY FIELD(cm.role,'admin','member')",[$gid]);
        ok('OK',$members);
    }

    // --- Search conversations ---
    if($action==='search_conversations'){
        $q=trim($_GET['q']??'');
        if(mb_strlen($q)<1) ok('OK',[]);
        $convs=$d->fetchAll("SELECT c.*,
            CASE WHEN c.type='private' THEN (SELECT fullname FROM users WHERE id=IF(c.user1_id=?,c.user2_id,c.user1_id)) ELSE c.group_name END as display_name,
            CASE WHEN c.type='private' THEN (SELECT avatar FROM users WHERE id=IF(c.user1_id=?,c.user2_id,c.user1_id)) ELSE c.group_avatar END as display_avatar
            FROM conversations c
            LEFT JOIN conversation_members cm ON c.id=cm.conversation_id
            WHERE (cm.user_id=? OR c.user1_id=? OR c.user2_id=?)
            AND (
                (c.type='private' AND (SELECT fullname FROM users WHERE id=IF(c.user1_id=?,c.user2_id,c.user1_id)) LIKE ?)
                OR (c.type='group' AND c.group_name LIKE ?)
            )
            GROUP BY c.id
            ORDER BY c.updated_at DESC LIMIT 20",
            [$uid,$uid,$uid,$uid,$uid,$uid,'%'.$q.'%','%'.$q.'%']);
        ok('OK',$convs);
    }

    // --- Search messages within conversation ---
    if($action==='search_messages'){
        $cid=intval($_GET['conversation_id']??0);
        $q=trim($_GET['q']??'');
        if(!$cid||mb_strlen($q)<1) ok('OK',[]);
        $msgs=$d->fetchAll("SELECT m.*,u.fullname as sender_name,u.avatar as sender_avatar FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=? AND m.content LIKE ? ORDER BY m.created_at DESC LIMIT 30",[$cid,'%'.$q.'%']);
        ok('OK',$msgs);
    }

    // --- Typing indicator check ---
    if($action==='typing_status'){
        $cid=intval($_GET['conversation_id']??0);
        if(!$cid) ok('OK',['typing'=>[]]);
        // Get members who are typing (cached, TTL 5s)
        $members=$d->fetchAll("SELECT user_id FROM conversation_members WHERE conversation_id=? AND user_id!=?",[$cid,$uid]);
        $typing=[];
        foreach($members as $m){
            $ts=cache_get('typing_'.$cid.'_'.$m['user_id']);
            if($ts&&(time()-intval($ts))<6){
                $u=$d->fetchOne("SELECT id,fullname,avatar FROM users WHERE id=?",[$m['user_id']]);
                if($u) $typing[]=$u;
            }
        }
        ok('OK',['typing'=>$typing]);
    }

    // --- Messages ---
    if($action==='messages'){
        $cid=intval($_GET['conversation_id']??0);
        if(!$cid) ok('OK',[]);
        // Access check
        $cv=$d->fetchOne("SELECT id,type FROM conversations WHERE id=?",[$cid]);
        if(!$cv) fail('Not found',404);
        if($cv['type']==='group'){
            if(!$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid])) fail('No access',403);
        }else{
            if(!$d->fetchOne("SELECT id FROM conversations WHERE id=? AND (user1_id=? OR user2_id=?)",[$cid,$uid,$uid])) fail('No access',403);
        }
        // Mark read
        try{$d->query("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?",[$cid,$uid]);}catch(\Throwable $e){}
        // Fetch (support after_id for polling)
        $afterId=intval($_GET['after_id']??0);
        $afterFilter=$afterId?" AND m.id > $afterId":'';
        $msgs=$d->fetchAll("SELECT m.id,m.conversation_id,m.sender_id,m.content,m.is_read,m.read_at,m.reactions,m.created_at,m.`type`,m.file_url,m.file_name,m.is_pinned,m.reply_to_id,u.fullname as sender_name,u.avatar as sender_avatar FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=?$afterFilter ORDER BY m.created_at ASC",[$cid]);
        ok('OK',$msgs);
    }

    // --- User info (for opening chat) ---
    if($action==='user_info'){
        $tid=intval($_GET['id']??0);
        $u=$d->fetchOne("SELECT id,fullname,avatar,username,shipping_company,is_online,last_active FROM users WHERE id=?",[$tid]);
        if(!$u) fail('Not found',404);
        $cv=$d->fetchOne("SELECT id,`status` as conv_status FROM conversations WHERE ((user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)) AND (type='private' OR type IS NULL)",[$uid,$tid,$tid,$uid]);
        $u['conversation_id']=$cv?intval($cv['id']):null;
        $u['conv_status']=$cv?$cv['conv_status']:null;
        ok('OK',$u);
    }

    // --- Online friends ---
    if($action==='online_friends'){
        try{
            $fr=$d->fetchAll("SELECT DISTINCT u.id,u.fullname,u.avatar,u.is_online,u.last_active,u.shipping_company FROM follows f1 JOIN follows f2 ON f1.following_id=f2.follower_id AND f1.follower_id=f2.following_id JOIN users u ON f1.following_id=u.id WHERE f1.follower_id=? AND u.id!=? ORDER BY u.is_online DESC,u.last_active DESC",[$uid,$uid]);
            ok('OK',$fr);
        }catch(\Throwable $e){ok('OK',[]);}
    }

    // --- Pending count ---
    if($action==='pending_count'){
        try{
            $cnt=$d->fetchOne("SELECT COUNT(*) as c FROM conversations WHERE (user1_id=? OR user2_id=?) AND `status`='pending' AND (type='private' OR type IS NULL)",[$uid,$uid]);
            echo json_encode(['success'=>true,'count'=>intval($cnt['c']??0)]);exit;
        }catch(\Throwable $e){echo json_encode(['success'=>true,'count'=>0]);exit;}
    }

    // --- Media in conversation ---
    if($action==='media'){
        $cid=intval($_GET['conversation_id']??0);
        $type=$_GET['type']??'image';
        $msgs=$d->fetchAll("SELECT id,file_url,file_name,`type`,created_at FROM messages WHERE conversation_id=? AND `type`=? AND file_url IS NOT NULL ORDER BY created_at DESC LIMIT 50",[$cid,$type]);
        ok('OK',$msgs);
    }

    // --- Search messages ---
    if($action==='search_messages'){
        $cid=intval($_GET['conversation_id']??0);
        $q=trim($_GET['q']??'');
        if(!$cid||!$q) ok('OK',[]);
        $msgs=$d->fetchAll("SELECT m.id,m.content,m.created_at,u.fullname as sender_name FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=? AND m.content LIKE ? ORDER BY m.created_at DESC LIMIT 20",[$cid,'%'.$q.'%']);
        ok('OK',$msgs);
    }

    // --- Pinned messages ---
    if($action==='pinned_messages'){
        $cid=intval($_GET['conversation_id']??0);
        $msgs=$d->fetchAll("SELECT m.*,u.fullname as sender_name FROM messages m LEFT JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=? AND m.is_pinned=1 ORDER BY m.created_at DESC",[$cid]);
        ok('OK',$msgs);
    }

    // --- Chat categories ---
    if($action==='get_categories'){
        $cats=$d->fetchAll("SELECT * FROM chat_categories WHERE user_id=? ORDER BY id",[$uid]);
        ok('OK',$cats);
    }
    if($action==='get_chat_category'){
        $cid=intval($_GET['conversation_id']??0);
        $cat=$d->fetchOne("SELECT category_id FROM chat_category_items WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        ok('OK',$cat?:['category_id'=>0]);
    }

    // --- Filter conversations ---
    if($action==='filter_conversations'){
        $f=$_GET['filter']??'';
        $cat=intval($_GET['category_id']??0);
        if($f==='unread'){
            $rows=$d->fetchAll("SELECT c.id,c.user1_id,c.user2_id,c.last_message,c.last_message_at FROM conversations c WHERE (c.user1_id=? OR c.user2_id=?) AND c.`status`='active' AND (c.type='private' OR c.type IS NULL) AND EXISTS(SELECT 1 FROM messages m WHERE m.conversation_id=c.id AND m.sender_id!=? AND m.is_read=0) ORDER BY c.last_message_at DESC",[$uid,$uid,$uid]);
        }elseif($f==='online'){
            $rows=$d->fetchAll("SELECT c.id,c.user1_id,c.user2_id,c.last_message,c.last_message_at FROM conversations c JOIN users u2 ON (CASE WHEN c.user1_id=? THEN c.user2_id ELSE c.user1_id END)=u2.id WHERE (c.user1_id=? OR c.user2_id=?) AND c.`status`='active' AND u2.is_online=1 ORDER BY c.last_message_at DESC",[$uid,$uid,$uid]);
        }else{
            $rows=[];
        }
        $result=[];
        foreach($rows as $c){
            $oid=($c['user1_id']==$uid)?$c['user2_id']:$c['user1_id'];
            $other=$d->fetchOne("SELECT id,fullname,avatar,shipping_company,is_online,last_active FROM users WHERE id=?",[$oid]);
            if(!$other) continue;
            $un=$d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND sender_id!=? AND is_read=0",[$c['id'],$uid]);
            $result[]=['id'=>$c['id'],'other_id'=>intval($oid),'other_name'=>$other['fullname'],'other_avatar'=>$other['avatar'],'other_ship'=>$other['shipping_company'],'other_online'=>$other['is_online'],'last_message'=>$c['last_message'],'last_message_at'=>$c['last_message_at'],'unread_count'=>intval($un['c']??0)];
        }
        ok('OK',$result);
    }

    ok('OK',[]);
}

// ========== POST ==========
if($method==='POST'){
    $uid=msgAuth();
    $input=json_decode(file_get_contents('php://input'),true);

    // === SEND MESSAGE (PDO direct) ===
    if($action==='send'){
        rate_enforce('msg_send',100,3600);
        $oid=intval($input['to_user_id']??0);
        $ct=trim($input['content']??'');
        $gid=intval($input['group_id']??0);
        $replyTo=isset($input['reply_to_id'])?intval($input['reply_to_id']):null;

        // Group message
        if($gid){
            if(!$ct) fail('Missing content');
            if(!$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$gid,$uid])) fail('Not a member');
            $ins=$pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,`type`,reply_to_id,created_at) VALUES (?,?,?,'text',?,NOW())");
            $ins->execute([$gid,$uid,$ct,$replyTo]);
            $mid=intval($pdo->lastInsertId());
            if(!$mid){$r=$pdo->query("SELECT MAX(id) as m FROM messages");$mid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
            $pdo->prepare("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?")->execute([$ct,$gid]);
            try{require_once __DIR__.'/../../includes/push-helper.php';$sn=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);$gn=$d->fetchOne("SELECT name FROM conversations WHERE id=?",[$gid]);$ms=$d->fetchAll("SELECT user_id FROM conversation_members WHERE conversation_id=? AND user_id!=?",[$gid,$uid]);foreach($ms as $m2){notifyUser($m2['user_id'],'Nhóm: '.($gn?$gn['name']:'Nhóm'),($sn?$sn['fullname']:'Ai đó').': '.mb_substr($ct,0,50),'message','/messages.html?group='.$gid);}}catch(\Throwable $e){}
            ok('OK',['id'=>$mid,'conversation_id'=>$gid]);
        }

        // Private message
        if(!$ct||!$oid) fail('Missing content or recipient');
        if(isBlocked($uid,$oid)) fail('Không thể gửi tin nhắn cho người dùng này');

        $f1=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$oid]);
        $f2=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$oid,$uid]);
        $mut=($f1&&$f2);

        $cv=$pdo->prepare("SELECT id FROM conversations WHERE ((user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)) AND (type='private' OR type IS NULL)");
        $cv->execute([$uid,$oid,$oid,$uid]);
        $row=$cv->fetch(PDO::FETCH_ASSOC);

        if(!$row){
            $st=$mut?'active':'pending';
            $ic=$pdo->prepare("INSERT INTO conversations (user1_id,user2_id,last_message,last_message_at,`status`) VALUES (?,?,?,NOW(),?)");
            $ic->execute([$uid,$oid,$ct,$st]);
            $cid=intval($pdo->lastInsertId());
            if(!$cid){$fc=$pdo->query("SELECT MAX(id) as m FROM conversations");$cid=intval($fc->fetch(PDO::FETCH_ASSOC)['m']);}
        }else{
            $cid=intval($row['id']);
            $pdo->prepare("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?")->execute([$ct,$cid]);
        }

        $im=$pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,reply_to_id,created_at) VALUES (?,?,?,?,NOW())");
        $im->execute([$cid,$uid,$ct,$replyTo]);
        $mid=intval($pdo->lastInsertId());
        if(!$mid){$r=$pdo->query("SELECT MAX(id) as m FROM messages");$mid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

        try{require_once __DIR__.'/../../includes/push-helper.php';$sn=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);notifyUser($oid,'Tin nhắn: '.($sn?$sn['fullname']:'Ai đó'),mb_substr($ct,0,60),'message','/messages.html?user='.$uid);}catch(\Throwable $e){}
        ok('OK',['id'=>$mid,'conversation_id'=>$cid]);
    }

    // === CREATE GROUP ===
    if($action==='create_group'){
        $name=trim($input['name']??'');
        if(!$name||mb_strlen($name)<2) fail('Tên nhóm tối thiểu 2 ký tự');
        $members=$input['member_ids']??[];
        $desc=trim($input['description']??'');
        $link=substr(md5('ss_'.time().$uid),0,12);

        try{
            $ins=$pdo->prepare("INSERT INTO conversations (type,name,creator_id,invite_link,last_message,last_message_at,`status`,description) VALUES ('group',?,?,?,?,NOW(),'active',?)");
            $ins->execute([$name,$uid,$link,$name.' - Nhóm mới',$desc]);
            $cid=intval($pdo->lastInsertId());
            if(!$cid){$r=$pdo->query("SELECT MAX(id) as m FROM conversations");$cid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        }catch(\Throwable $e){
            fail('Create group error: '.$e->getMessage());
        }

        try{$pdo->prepare("INSERT INTO conversation_members (conversation_id,user_id,role) VALUES (?,?,'admin')")->execute([$cid,$uid]);}catch(\Throwable $e){}
        foreach($members as $mid2){
            $mid2=intval($mid2);
            if($mid2&&$mid2!=$uid){try{$pdo->prepare("INSERT IGNORE INTO conversation_members (conversation_id,user_id,role) VALUES (?,?,'member')")->execute([$cid,$mid2]);}catch(\Throwable $e){}}
        }
        ok('OK',['conversation_id'=>$cid,'invite_link'=>$link]);
    }

    // === ACCEPT pending ===
    if($action==='accept'){
        $cid=intval($input['conversation_id']??0);
        $d->query("UPDATE conversations SET `status`='active' WHERE id=? AND (user1_id=? OR user2_id=?)",[$cid,$uid,$uid]);
        ok('OK');
    }

    // === MARK READ ===
    if($action==='read'){
        $cid=intval($input['conversation_id']??0);
        $d->query("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?",[$cid,$uid]);
        ok('OK');
    }

    // === DELETE MESSAGE ===
    if($action==='delete_message'){
        $mid2=intval($input['message_id']??0);
        $msg=$d->fetchOne("SELECT sender_id,created_at FROM messages WHERE id=?",[$mid2]);
        if(!$msg) fail('Not found',404);
        if(intval($msg['sender_id'])!==$uid) fail('Chỉ người gửi mới xóa được',403);
        if(strtotime($msg['created_at'])<time()-3600) fail('Chỉ xóa được trong 1 giờ');
        $d->query("DELETE FROM messages WHERE id=?",[$mid2]);
        ok('Đã xóa');
    }

    // === EDIT MESSAGE ===
    if($action==='edit_message'){
        $mid2=intval($input['message_id']??0);
        $newContent=trim($input['content']??'');
        if(!$newContent) fail('Nội dung trống');
        $msg=$d->fetchOne("SELECT sender_id,created_at FROM messages WHERE id=?",[$mid2]);
        if(!$msg) fail('Not found',404);
        if(intval($msg['sender_id'])!==$uid) fail('Chỉ người gửi mới sửa được',403);
        if(strtotime($msg['created_at'])<time()-900) fail('Chỉ sửa được trong 15 phút');
        $d->query("UPDATE messages SET content=? WHERE id=?",[$newContent,$mid2]);
        ok('Đã sửa');
    }

    // === MUTE CONVERSATION ===
    if($action==='mute_conversation'){
        $cid=intval($input['conversation_id']??0);
        $d->query("UPDATE conversations SET is_muted=IF(is_muted=1,0,1) WHERE id=?",[$cid]);
        ok('OK');
    }

    // === PIN/UNPIN MESSAGE ===
    if($action==='pin_message'){
        $mid2=intval($input['message_id']??0);
        $d->query("UPDATE messages SET is_pinned=IF(is_pinned=1,0,1) WHERE id=?",[$mid2]);
        ok('OK');
    }

    // === RENAME GROUP ===
    if($action==='rename_group'){
        $cid=intval($input['conversation_id']??0);
        $name=trim($input['name']??'');
        if(!$name) fail('Tên trống');
        $member=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        if(!$member) fail('Not a member',403);
        $d->query("UPDATE conversations SET name=? WHERE id=?",[$name,$cid]);
        ok('OK');
    }

    // === UPDATE GROUP ===
    if($action==='update_group'){
        $cid=intval($input['conversation_id']??0);
        $member=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        if(!$member) fail('Not a member',403);
        if(isset($input['description'])) $d->query("UPDATE conversations SET description=? WHERE id=?",[trim($input['description']),$cid]);
        if(isset($input['name'])) $d->query("UPDATE conversations SET name=? WHERE id=?",[trim($input['name']),$cid]);
        ok('OK');
    }

    // === ADD MEMBER ===
    if($action==='add_member'){
        $cid=intval($input['conversation_id']??0);
        $mid2=intval($input['user_id']??0);
        if(!$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid])) fail('Not a member');
        try{$pdo->prepare("INSERT IGNORE INTO conversation_members (conversation_id,user_id,role) VALUES (?,?,'member')")->execute([$cid,$mid2]);}catch(\Throwable $e){}
        ok('OK');
    }

    // === REMOVE MEMBER (admin only) ===
    if($action==='remove_member'){
        $cid=intval($input['conversation_id']??0);
        $mid2=intval($input['user_id']??0);
        $admin=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        if(!$admin||$admin['role']!=='admin') fail('Admin only',403);
        $d->query("DELETE FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$mid2]);
        ok('OK');
    }

    // === LEAVE GROUP ===
    if($action==='leave_group'){
        $cid=intval($input['conversation_id']??0);
        $member=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        if(!$member) fail('Not a member');
        if($member['role']==='admin'){
            $others=$d->fetchAll("SELECT user_id FROM conversation_members WHERE conversation_id=? AND user_id!=? LIMIT 1",[$cid,$uid]);
            if($others) $d->query("UPDATE conversation_members SET role='admin' WHERE conversation_id=? AND user_id=?",[$cid,$others[0]['user_id']]);
        }
        $d->query("DELETE FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        ok('Đã rời nhóm');
    }

    // === DELETE CONVERSATION ===
    if($action==='delete_conversation'){
        $cid=intval($input['conversation_id']??0);
        $cv=$d->fetchOne("SELECT type,creator_id FROM conversations WHERE id=?",[$cid]);
        if(!$cv) fail('Not found',404);
        if($cv['type']==='group'){
            if(intval($cv['creator_id'])!==$uid){$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);if(($user['role']??'')!=='admin') fail('Admin only',403);}
            $d->query("DELETE FROM conversation_members WHERE conversation_id=?",[$cid]);
        }
        $d->query("DELETE FROM messages WHERE conversation_id=?",[$cid]);
        $d->query("DELETE FROM conversations WHERE id=?",[$cid]);
        ok('Đã xóa');
    }

    // === CHAT CATEGORIES ===
    if($action==='save_category'){
        $name=trim($input['name']??'');$icon=$input['icon']??'📁';$color=$input['color']??'#0084ff';$ci=intval($input['id']??0);
        if($ci>0){$d->query("UPDATE chat_categories SET name=?,icon=?,color=? WHERE id=? AND user_id=?",[$name,$icon,$color,$ci,$uid]);}
        else{$pdo->prepare("INSERT INTO chat_categories (user_id,name,icon,color) VALUES (?,?,?,?)")->execute([$uid,$name,$icon,$color]);$ci=intval($pdo->lastInsertId());if(!$ci){$r=$pdo->query("SELECT MAX(id) as m FROM chat_categories");$ci=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}}
        ok('OK',['id'=>$ci]);
    }
    if($action==='delete_category'){
        $ci=intval($input['id']??0);
        $d->query("DELETE FROM chat_category_items WHERE category_id=? AND user_id=?",[$ci,$uid]);
        $d->query("DELETE FROM chat_categories WHERE id=? AND user_id=?",[$ci,$uid]);
        ok('OK');
    }
    if($action==='set_chat_category'){
        $cid=intval($input['conversation_id']??0);$cat=intval($input['category_id']??0);
        $d->query("DELETE FROM chat_category_items WHERE conversation_id=? AND user_id=?",[$cid,$uid]);
        if($cat>0) $pdo->prepare("INSERT INTO chat_category_items (conversation_id,user_id,category_id) VALUES (?,?,?)")->execute([$cid,$uid,$cat]);
        ok('OK');
    }

    // === TYPING INDICATOR ===
    if($action==='typing'){
        $cid=intval($input['conversation_id']??0);
        if(!$cid) fail('Missing conversation_id');
        // Store in cache (TTL 5s)
        try{cache_set('typing_'.$cid.'_'.$uid, time(), 5);}catch(\Throwable $e){}
        ok('OK');
    }

    // === READ RECEIPTS ===
    if($action==='mark_read'){
        $cid=intval($input['conversation_id']??0);
        if(!$cid) fail('Missing conversation_id');
        $d->query("UPDATE messages SET is_read=1, read_at=NOW() WHERE conversation_id=? AND sender_id!=? AND is_read=0",[$cid,$uid]);
        ok('Đã đọc');
    }

    // === FORWARD MESSAGE ===
    if($action==='forward'){
        $mid=intval($input['message_id']??0);
        $toCid=intval($input['to_conversation_id']??0);
        if(!$mid||!$toCid) fail('Missing data');
        $msg=$d->fetchOne("SELECT content,`type`,file_url,file_name FROM messages WHERE id=?",[$mid]);
        if(!$msg) fail('Message not found',404);
        // Verify access to target conversation
        $access=$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$toCid,$uid]);
        if(!$access){$access=$d->fetchOne("SELECT id FROM conversations WHERE id=? AND (user1_id=? OR user2_id=?)",[$toCid,$uid,$uid]);}
        if(!$access) fail('No access to target',403);
        $fwdContent=$msg['content']?'[Chuyển tiếp] '.$msg['content']:'[Chuyển tiếp]';
        $pdo->prepare("INSERT INTO messages (conversation_id,sender_id,content,`type`,file_url,file_name,created_at) VALUES (?,?,?,?,?,?,NOW())")->execute([$toCid,$uid,$fwdContent,$msg['type']??'text',$msg['file_url'],$msg['file_name']]);
        $d->query("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?",[$fwdContent,$toCid]);
        ok('Đã chuyển tiếp!');
    }

    // === MESSAGE REACTIONS ===
    if($action==='react'){
        $mid=intval($input['message_id']??0);
        $emoji=trim($input['emoji']??'');
        if(!$mid||!$emoji) fail('Missing data');
        // Store reaction as JSON in messages.reactions column (or separate table)
        $msg=$d->fetchOne("SELECT id,reactions FROM messages WHERE id=?",[$mid]);
        if(!$msg) fail('Message not found',404);
        $reactions=json_decode($msg['reactions']??'{}',true)?:[];
        $key=$emoji;
        if(!isset($reactions[$key])) $reactions[$key]=[];
        if(in_array($uid,$reactions[$key])){
            $reactions[$key]=array_values(array_diff($reactions[$key],[$uid]));
            if(empty($reactions[$key])) unset($reactions[$key]);
        }else{
            $reactions[$key][]=$uid;
        }
        $d->query("UPDATE messages SET reactions=? WHERE id=?",[json_encode($reactions),$mid]);
        ok('OK',['reactions'=>$reactions]);
    }

    fail('Action không hợp lệ');
}

fail('Method không hỗ trợ',405);
