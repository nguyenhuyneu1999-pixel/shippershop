<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
$d=db();
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';

function getMsgUserId(){
  if(!empty($_SESSION["user_id"])) return intval($_SESSION["user_id"]);
  try{ return getAuthUserId(); }catch(Throwable $e){}
  echo json_encode(['success'=>false,'message'=>'Vui lòng đăng nhập']);exit;
}

// ===== SEARCH & FILTER CHAT =====
if($action==="filter_conversations"){
    $uid=getMsgUserId();
    $f=$_GET["filter"]??"";
    $q=trim($_GET["search"]??"");
    $cid=intval($_GET["category_id"]??0);
    $sql="SELECT c.id,c.user1_id,c.user2_id,c.last_message,c.last_message_at,
        CASE WHEN c.user1_id=? THEN c.user2_id ELSE c.user1_id END as other_id,
        u.fullname as other_name,u.avatar as other_avatar,u.is_online as other_online,
        u.last_active as other_last_active,u.shipping_company as other_ship,
        (SELECT COUNT(*) FROM messages ms WHERE ms.conversation_id=c.id AND ms.sender_id!=? AND ms.is_read=0) as unread_count,
        cci.category_id
        FROM conversations c
        JOIN users u ON u.id=CASE WHEN c.user1_id=? THEN c.user2_id ELSE c.user1_id END
        LEFT JOIN chat_category_items cci ON cci.conversation_id=c.id AND cci.user_id=?
        WHERE(c.user1_id=? OR c.user2_id=?) AND c.`status`=? AND (c.type='private' OR c.type IS NULL)";
    $p=[$uid,$uid,$uid,$uid,$uid,$uid,"active"];
    if($f==="online") $sql.=" AND u.is_online=1";
    elseif($f==="unread") $sql.=" HAVING unread_count>0";
    elseif($f==="strangers"){
        $sql.=" AND NOT EXISTS(SELECT 1 FROM friends ff WHERE((ff.user_id=? AND ff.friend_id=u.id)OR(ff.user_id=u.id AND ff.friend_id=?))AND ff.`status`=?)";
        $p[]=$uid;$p[]=$uid;$p[]="accepted";
    } elseif($f==="category"&&$cid>0){
        $sql.=" AND cci.category_id=?";$p[]=$cid;
    }
    if($q!==""){$sql.=" AND u.fullname LIKE ?";$p[]="%".$q."%";}
    $sql.=" ORDER BY c.last_message_at DESC LIMIT 50";
    echo json_encode(["success"=>true,"data"=>db()->fetchAll($sql,$p)?:[]]);exit;
}

if($action==="get_categories"){
    $uid=getMsgUserId();
    echo json_encode(["success"=>true,"data"=>db()->fetchAll("SELECT cc.*,(SELECT COUNT(*) FROM chat_category_items ci WHERE ci.category_id=cc.id) as conv_count FROM chat_categories cc WHERE cc.user_id=? ORDER BY cc.sort_order,cc.name",[$uid])?:[]]);exit;
}

if($action==="save_category"){
    $uid=getMsgUserId();
    $in=json_decode(file_get_contents("php://input"),true);
    $n=trim($in["name"]??"");$ic=trim($in["icon"]??"📁");$co=trim($in["color"]??"#0084ff");$ci=intval($in["id"]??0);
    if($n===""){echo json_encode(["success"=>false]);exit;}
    if($ci>0){db()->query("UPDATE chat_categories SET name=?,icon=?,color=? WHERE id=? AND user_id=?",[$n,$ic,$co,$ci,$uid]);}
    else{db()->query("INSERT INTO chat_categories(user_id,name,icon,color)VALUES(?,?,?,?)",[$uid,$n,$ic,$co]);$ci=db()->getLastInsertId();}
    echo json_encode(["success"=>true,"data"=>["id"=>$ci]]);exit;
}

if($action==="delete_category"){
    $uid=getMsgUserId();
    $in=json_decode(file_get_contents("php://input"),true);$ci=intval($in["id"]??0);
    db()->query("DELETE FROM chat_category_items WHERE category_id=? AND user_id=?",[$ci,$uid]);
    db()->query("DELETE FROM chat_categories WHERE id=? AND user_id=?",[$ci,$uid]);
    echo json_encode(["success"=>true]);exit;
}

if($action==="set_chat_category"){
    $uid=getMsgUserId();
    $in=json_decode(file_get_contents("php://input"),true);
    $cv=intval($in["conversation_id"]??0);$ci=intval($in["category_id"]??0);
    if($cv<=0){echo json_encode(["success"=>false]);exit;}
    db()->query("DELETE FROM chat_category_items WHERE user_id=? AND conversation_id=?",[$uid,$cv]);
    if($ci>0) db()->query("INSERT INTO chat_category_items(user_id,conversation_id,category_id)VALUES(?,?,?)",[$uid,$cv,$ci]);
    echo json_encode(["success"=>true]);exit;
}

if($action==="get_chat_category"){
    $uid=getMsgUserId();
    $cv=intval($_GET["conversation_id"]??0);
    $row=db()->fetchOne("SELECT category_id FROM chat_category_items WHERE user_id=? AND conversation_id=?",[$uid,$cv]);
    echo json_encode(["success"=>true,"data"=>["category_id"=>$row?intval($row["category_id"]):0]]);exit;
}
// ===== END FILTER =====

// ===== UPLOAD MESSAGE =====
if($action==="upload_message"){
    $userId=getMsgUserId();
    $toUserId=intval($_POST["to_user_id"]??0);
    $groupId=intval($_POST["group_id"]??0);
    $type=$_POST["type"]??"image";
    
    // For groups: verify membership and use group conv directly
    if($groupId>0){
        $member=db()->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$groupId,$userId]);
        if(!$member){echo json_encode(["success"=>false,"message"=>"Not a member"]);exit;}
        $convId=$groupId;
    } else {
        if($toUserId<=0){echo json_encode(["success"=>false]);exit;}
        $convId=null;
    }

    if($type==="location"){
        $lat=$_POST["lat"]??"";$lng=$_POST["lng"]??"";
        $content="📍 Vị trí";
        $fileUrl="https://maps.google.com/maps?q=".$lat.",".$lng;
        if(!$convId){
            $conv=db()->fetchOne("SELECT id FROM conversations WHERE((user1_id=? AND user2_id=?)OR(user1_id=? AND user2_id=?))AND `status` IN(?,?)",[$userId,$toUserId,$toUserId,$userId,"active","pending"]);
            if(!$conv){
                db()->query("INSERT INTO conversations(user1_id,user2_id,last_message,last_message_at,`status`)VALUES(?,?,?,NOW(),?)",[$userId,$toUserId,$content,"pending"]);
                $convId=db()->getLastInsertId();
            } else { $convId=$conv["id"]; }
        }
        db()->query("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?",[$content,$convId]);
        db()->query("INSERT INTO messages(conversation_id,sender_id,content,`type`,file_url,file_name)VALUES(?,?,?,?,?,?)",[$convId,$userId,$content,"location",$fileUrl,""]);
        echo json_encode(["success"=>true,"data"=>["conversation_id"=>$convId]]);exit;
    }

    if(!isset($_FILES["file"])){echo json_encode(["success"=>false]);exit;}
    $file=$_FILES["file"];
    $ext=strtolower(pathinfo($file["name"],PATHINFO_EXTENSION));
    $allowed=["jpg","jpeg","png","gif","webp","pdf","doc","docx","xls","xlsx","zip","rar","mp4","mp3","mov","webm","avi"];
    if(!in_array($ext,$allowed)||$file["size"]>20*1024*1024){echo json_encode(["success"=>false]);exit;}

    $imgExts=["jpg","jpeg","png","gif","webp"];
    $vidExts=["mp4","mov","avi","webm"];
    $msgType=in_array($ext,$imgExts)?"image":(in_array($ext,$vidExts)?"video":"file");

    $dir="/home/nhshiw2j/public_html/uploads/messages/";
    if(!is_dir($dir)) mkdir($dir,0755,true);
    $newName=time()."_".mt_rand(1000,9999).".".$ext;
    move_uploaded_file($file["tmp_name"],$dir.$newName);
    $fileUrl="/uploads/messages/".$newName;
    $fileName=$file["name"];
    $content=($msgType==="image"?"[Hình ảnh]":($msgType==="video"?"[Video]":"[File] ".$fileName));

    if(!$convId){
        $conv=db()->fetchOne("SELECT id FROM conversations WHERE((user1_id=? AND user2_id=?)OR(user1_id=? AND user2_id=?))AND `status` IN(?,?)",[$userId,$toUserId,$toUserId,$userId,"active","pending"]);
        if(!$conv){
            db()->query("INSERT INTO conversations(user1_id,user2_id,last_message,last_message_at,`status`)VALUES(?,?,?,NOW(),?)",[$userId,$toUserId,$content,"pending"]);
            $convId=db()->getLastInsertId();
        } else { $convId=$conv["id"]; }
    }
    db()->query("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?",[$content,$convId]);
    db()->query("INSERT INTO messages(conversation_id,sender_id,content,`type`,file_url,file_name)VALUES(?,?,?,?,?,?)",[$convId,$userId,$content,$msgType,$fileUrl,$fileName]);
    echo json_encode(["success"=>true,"data"=>["conversation_id"=>$convId,"type"=>$msgType,"file_url"=>$fileUrl,"file_name"=>$fileName]]);exit;
}
// ===== END UPLOAD =====

if($method==='GET'){
try{
$userId=getMsgUserId();
try{$d->query("UPDATE users SET is_online=1, last_active=NOW() WHERE id=?",[$userId]);}catch(Throwable $e){}

if($action==='conversations'){
  $tab=$_GET['tab']??'active';
  $sf=$tab==='pending'?'pending':'active';
  $rows=$d->fetchAll("SELECT id,user1_id,user2_id,last_message,last_message_at,`status` FROM conversations WHERE (user1_id=? OR user2_id=?) AND `status`=? AND (type='private' OR type IS NULL) ORDER BY last_message_at DESC",[$userId,$userId,$sf]);
  $result=[];
  foreach($rows as $c){
    $oid=($c['user1_id']==$userId)?$c['user2_id']:$c['user1_id'];
    $other=$d->fetchOne("SELECT id,fullname,avatar,shipping_company,is_online,last_active FROM users WHERE id=?",[$oid]);
    if(!$other) continue;
    $un=$d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id=? AND sender_id!=? AND is_read=0",[$c['id'],$userId]);
    $result[]=[
      'id'=>$c['id'],'user1_id'=>$c['user1_id'],'user2_id'=>$c['user2_id'],
      'last_message'=>$c['last_message'],'last_message_at'=>$c['last_message_at'],
      'other_id'=>$oid,'other_name'=>$other['fullname'],'other_avatar'=>$other['avatar'],
      'other_ship'=>$other['shipping_company'],'other_online'=>$other['is_online'],
      'other_last_active'=>$other['last_active'],
      'unread_count'=>intval($un['c']??0),'type'=>'private'
    ];
  }
  echo json_encode(['success'=>true,'data'=>$result]);exit;
}

// ===== GROUP CONVERSATIONS =====
if($action==='group_conversations'){
  $groups=$d->fetchAll("SELECT c.id,c.name,c.avatar,c.last_message,c.last_message_at,c.description,c.invite_link,c.creator_id,
    (SELECT COUNT(*) FROM conversation_members WHERE conversation_id=c.id) as member_count,
    (SELECT COUNT(*) FROM messages WHERE conversation_id=c.id AND sender_id!=? AND is_read=0) as unread_count
    FROM conversations c
    JOIN conversation_members cm ON cm.conversation_id=c.id AND cm.user_id=?
    WHERE c.type='group' AND c.`status`='active'
    ORDER BY c.last_message_at DESC",[$userId,$userId]);
  echo json_encode(['success'=>true,'data'=>$groups?:[]]);exit;
}

// ===== GROUP INFO =====
if($action==='group_info'){
  $cid=intval($_GET['conversation_id']??0);
  $member=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  if(!$member){echo json_encode(['success'=>false,'message'=>'Not a member']);exit;}
  $g=$d->fetchOne("SELECT c.*, (SELECT COUNT(*) FROM conversation_members WHERE conversation_id=c.id) as member_count FROM conversations c WHERE c.id=? AND c.type='group'",[$cid]);
  if(!$g){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
  $g['my_role']=$member['role'];
  echo json_encode(['success'=>true,'data'=>$g]);exit;
}

// ===== GROUP MEMBERS =====
if($action==='group_members'){
  $cid=intval($_GET['conversation_id']??0);
  $member=$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  if(!$member){echo json_encode(['success'=>false,'message'=>'Not a member']);exit;}
  $members=$d->fetchAll("SELECT cm.role, cm.nickname, cm.joined_at, u.id, u.fullname, u.avatar, u.username, u.shipping_company, u.is_online, u.last_active
    FROM conversation_members cm JOIN users u ON cm.user_id=u.id WHERE cm.conversation_id=?
    ORDER BY FIELD(cm.role,'admin','member'), cm.joined_at",[$cid]);
  echo json_encode(['success'=>true,'data'=>$members?:[]]);exit;
}

// ===== CONVERSATION MEDIA =====
if($action==='media'){
  $cid=intval($_GET['conversation_id']??0);
  $type=$_GET['type']??'image';
  // Check access (private or group)
  $hasAccess=false;
  $cv=$d->fetchOne("SELECT type FROM conversations WHERE id=?",[$cid]);
  if($cv){
    if($cv['type']==='group'){
      $hasAccess=(bool)$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
    }else{
      $hasAccess=(bool)$d->fetchOne("SELECT id FROM conversations WHERE id=? AND (user1_id=? OR user2_id=?)",[$cid,$userId,$userId]);
    }
  }
  if(!$hasAccess){echo json_encode(['success'=>false,'message'=>'No access']);exit;}
  
  $where="m.conversation_id=?";$params=[$cid];
  if($type==='image'){$where.=" AND m.type='image'";}
  elseif($type==='file'){$where.=" AND m.type='file'";}
  elseif($type==='link'){$where.=" AND m.content LIKE '%http%'";}
  else{$where.=" AND m.type IN ('image','file')";}
  
  $items=$d->fetchAll("SELECT m.id, m.sender_id, m.content, m.type, m.file_url, m.file_name, m.created_at, u.fullname as sender_name
    FROM messages m JOIN users u ON m.sender_id=u.id WHERE $where ORDER BY m.created_at DESC LIMIT 100",$params);
  echo json_encode(['success'=>true,'data'=>$items?:[]]);exit;
}

// ===== PINNED MESSAGES =====
if($action==='pinned_messages'){
  $cid=intval($_GET['conversation_id']??0);
  $pins=$d->fetchAll("SELECT m.id, m.content, m.type, m.file_url, m.created_at, u.fullname as sender_name, p.pinned_by, pu.fullname as pinned_by_name
    FROM pinned_messages p JOIN messages m ON p.message_id=m.id JOIN users u ON m.sender_id=u.id JOIN users pu ON p.pinned_by=pu.id
    WHERE p.conversation_id=? ORDER BY p.created_at DESC",[$cid]);
  echo json_encode(['success'=>true,'data'=>$pins?:[]]);exit;
}

if($action==='messages'){
  $cid=intval($_GET['conversation_id']??0);
  if(!$cid){echo json_encode(['success'=>true,'data'=>[]]);exit;}
  // Check access: private (user1/user2) or group (conversation_members)
  $cv=$d->fetchOne("SELECT id, type FROM conversations WHERE id=?",[$cid]);
  if(!$cv){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
  $hasAccess=false;
  if($cv['type']==='group'){
    $hasAccess=(bool)$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  }else{
    $hasAccess=(bool)$d->fetchOne("SELECT id FROM conversations WHERE id=? AND (user1_id=? OR user2_id=?)",[$cid,$userId,$userId]);
  }
  if(!$hasAccess){echo json_encode(['success'=>false,'message'=>'No access']);exit;}
  $d->query("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?",[$cid,$userId]);
  $msgs=$d->fetchAll("SELECT m.id,m.conversation_id,m.sender_id,m.content,m.is_read,m.created_at,m.`type`,m.file_url,m.file_name,m.is_pinned,u.fullname as sender_name,u.avatar as sender_avatar FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=? ORDER BY m.created_at ASC",[$cid]);
  echo json_encode(['success'=>true,'data'=>$msgs]);exit;
}

if($action==='user_info'){
  $uid=intval($_GET['id']??0);
  $u=$d->fetchOne("SELECT id,fullname,avatar,username,shipping_company,is_online,last_active FROM users WHERE id=?",[$uid]);
  if(!$u){echo json_encode(['success'=>false]);exit;}
  $cv=$d->fetchOne("SELECT id,`status` as conv_status FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)",[$userId,$uid,$uid,$userId]);
  $u['conversation_id']=$cv?intval($cv['id']):null;
  $u['conv_status']=$cv?$cv['conv_status']:null;
  echo json_encode(['success'=>true,'data'=>$u]);exit;
}

if($action==='online_friends'){
  try{
  $fr=$d->fetchAll("SELECT DISTINCT u.id,u.fullname,u.avatar,u.is_online,u.last_active,u.shipping_company FROM follows f1 JOIN follows f2 ON f1.following_id=f2.follower_id AND f1.follower_id=f2.following_id JOIN users u ON f1.following_id=u.id WHERE f1.follower_id=? AND u.id!=? ORDER BY u.is_online DESC,u.last_active DESC",[$userId,$userId]);
  echo json_encode(['success'=>true,'data'=>$fr]);
  }catch(Throwable $e){echo json_encode(['success'=>true,'data'=>[]]);}
  exit;
}

if($action==='pending_count'){
  try{
  $cnt=$d->fetchOne("SELECT COUNT(*) as cnt FROM conversations WHERE (user1_id=? OR user2_id=?) AND `status`='pending'",[$userId,$userId]);
  echo json_encode(['success'=>true,'count'=>intval($cnt['cnt']??0)]);
  }catch(Throwable $e){echo json_encode(['success'=>true,'count'=>0]);}
  exit;
}

echo json_encode(['success'=>true,'data'=>[]]);
}catch(Throwable $e){
  echo json_encode(['success'=>false,'message'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine()]);
}
exit;
}

if($method==='POST'){
try{
$userId=getMsgUserId();
$input=json_decode(file_get_contents('php://input'),true);

if($action==='send'){
  // Feature gate: check monthly message limit
  $limitErr = checkLimit($userId, 'messages_per_month');
  if ($limitErr) { echo json_encode(['success'=>false,'message'=>$limitErr,'upgrade'=>true]); exit; }
  $oid=intval($input['to_user_id']??0);$ct=trim($input['content']??'');
  $gid=intval($input['group_id']??0);
  
  // Group message
  if($gid){
    if(!$ct){echo json_encode(['success'=>false,'message'=>'Missing content']);exit;}
    $member=$d->fetchOne("SELECT id FROM conversation_members WHERE conversation_id=? AND user_id=?",[$gid,$userId]);
    if(!$member){echo json_encode(['success'=>false,'message'=>'Not a member']);exit;}
    $d->query("INSERT INTO messages (conversation_id,sender_id,content,type,created_at) VALUES (?,?,?,'text',NOW())",[$gid,$userId,$ct]);
    $mid=$d->getLastInsertId();
    $d->query("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?",[$ct,$gid]);
    // Push to group members (except sender)
    try{
      require_once __DIR__.'/../includes/push-helper.php';
      $sender=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$userId]);
      $grp=$d->fetchOne("SELECT name FROM conversations WHERE id=?",[$gid]);
      $members=$d->fetchAll("SELECT user_id FROM conversation_members WHERE conversation_id=? AND user_id!=?",[$gid,$userId]);
      $sName=$sender?$sender['fullname']:'Ai đó';
      $gName=$grp?$grp['name']:'Nhóm';
      $preview=mb_substr($ct,0,50);
      foreach($members as $m){notifyUser($m['user_id'],'Nhóm: '.$gName,$sName.': '.$preview,'message','/messages.html?group='.$gid);}
    }catch(Throwable $e){}
    echo json_encode(['success'=>true,'data'=>['id'=>$mid,'conversation_id'=>$gid]]);exit;
  }
  
  // Private message (existing logic)
  if(!$ct||!$oid){echo json_encode(['success'=>false,'message'=>'Missing']);exit;}
  $f1=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$userId,$oid]);
  $f2=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$oid,$userId]);
  $mut=($f1&&$f2);
  $cv=$d->fetchOne("SELECT id FROM conversations WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)",[$userId,$oid,$oid,$userId]);
  if(!$cv){
    $st=$mut?'active':'pending';
    $d->query("INSERT INTO conversations (user1_id,user2_id,last_message,last_message_at,`status`) VALUES (?,?,?,NOW(),?)",[$userId,$oid,$ct,$st]);
    $cid=$d->getLastInsertId();
  }else{$cid=$cv['id'];$d->query("UPDATE conversations SET last_message=?,last_message_at=NOW() WHERE id=?",[$ct,$cid]);}
  $d->query("INSERT INTO messages (conversation_id,sender_id,content,created_at) VALUES (?,?,?,NOW())",[$cid,$userId,$ct]);
  $mid=$d->getLastInsertId();
  // Push notification to recipient
  try{
    require_once __DIR__.'/../includes/push-helper.php';
    $sender=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[$userId]);
    $sName=$sender?$sender['fullname']:'Ai đó';
    $preview=mb_substr($ct,0,60);
    notifyUser($oid,'Tin nhắn: '.$sName,$preview,'message','/messages.html?user='.$userId);
  }catch(Throwable $e){}
  echo json_encode(['success'=>true,'data'=>['id'=>$mid,'conversation_id'=>$cid]]);exit;
}
if($action==='accept'){
  $cid=intval($input['conversation_id']??0);
  $d->query("UPDATE conversations SET `status`='active' WHERE id=? AND (user1_id=? OR user2_id=?)",[$cid,$userId,$userId]);
  echo json_encode(['success'=>true]);exit;
}
if($action==='read'){
  $cid=intval($input['conversation_id']??0);
  $d->query("UPDATE messages SET is_read=1 WHERE conversation_id=? AND sender_id!=?",[$cid,$userId]);
  echo json_encode(['success'=>true]);exit;
}

// ===== CREATE GROUP =====
if($action==='create_group'){
  $name=trim($input['name']??'');
  if(!$name||mb_strlen($name)<2){echo json_encode(['success'=>false,'message'=>'Tên nhóm ít nhất 2 ký tự']);exit;}
  $members=$input['member_ids']??[];
  $link=substr(md5("ss_".time().$userId),0,12);
  $d->query("INSERT INTO conversations (type,name,creator_id,invite_link,last_message,last_message_at,`status`,description) VALUES ('group',?,?,?,?,NOW(),'active',?)",[$name,$userId,$link,$name.' - Nhóm mới',$input['description']??'']);
  $cid=$d->getLastInsertId();
  if(!$cid){$cid=$d->fetchOne("SELECT MAX(id) as m FROM conversations",[]); $cid=$cid['m'];}
  $d->query("INSERT INTO conversation_members (conversation_id,user_id,role) VALUES (?,?,'admin')",[$cid,$userId]);
  foreach($members as $mid){
    $mid=intval($mid);
    if($mid&&$mid!=$userId){try{$d->query("INSERT IGNORE INTO conversation_members (conversation_id,user_id,role) VALUES (?,?,'member')",[$cid,$mid]);}catch(Throwable $e){}}
  }
  echo json_encode(['success'=>true,'data'=>['conversation_id'=>intval($cid),'invite_link'=>$link]]);exit;
}

// ===== ADD GROUP MEMBER =====
if($action==='add_member'){
  $cid=intval($input['conversation_id']??0);
  $mid=intval($input['user_id']??0);
  $admin=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  if(!$admin){echo json_encode(['success'=>false,'message'=>'Không phải thành viên']);exit;}
  try{$d->query("INSERT IGNORE INTO conversation_members (conversation_id,user_id,role) VALUES (?,?,'member')",[$cid,$mid]);}catch(Throwable $e){}
  echo json_encode(['success'=>true]);exit;
}

// ===== LEAVE GROUP =====
if($action==='leave_group'){
  $cid=intval($input['conversation_id']??0);
  $member=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  if(!$member){echo json_encode(['success'=>false,'message'=>'Không phải thành viên']);exit;}
  if($member['role']==='admin'){
    $others=$d->fetchAll("SELECT user_id FROM conversation_members WHERE conversation_id=? AND user_id!=? LIMIT 1",[$cid,$userId]);
    if($others){$d->query("UPDATE conversation_members SET role='admin' WHERE conversation_id=? AND user_id=?",[$cid,$others[0]['user_id']]);}
  }
  $d->query("DELETE FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  echo json_encode(['success'=>true]);exit;
}

// ===== PIN MESSAGE =====
if($action==='pin_message'){
  $cid=intval($input['conversation_id']??0);
  $mid=intval($input['message_id']??0);
  try{$d->query("INSERT IGNORE INTO pinned_messages (conversation_id,message_id,pinned_by) VALUES (?,?,?)",[$cid,$mid,$userId]);
  $d->query("UPDATE messages SET is_pinned=1 WHERE id=?",[$mid]);}catch(Throwable $e){}
  echo json_encode(['success'=>true]);exit;
}

// ===== DELETE CONVERSATION (for both private and group) =====
if($action==='delete_conversation'){
  $cid=intval($input['conversation_id']??0);
  $d->query("DELETE FROM messages WHERE conversation_id=?",[$cid]);
  echo json_encode(['success'=>true]);exit;
}

// ===== UPDATE GROUP =====
if($action==='update_group'){
  $cid=intval($input['conversation_id']??0);
  $admin=$d->fetchOne("SELECT role FROM conversation_members WHERE conversation_id=? AND user_id=?",[$cid,$userId]);
  if(!$admin||$admin['role']!=='admin'){echo json_encode(['success'=>false,'message'=>'Không có quyền']);exit;}
  $updates=[];$params=[];
  if(isset($input['name'])){$updates[]="name=?";$params[]=$input['name'];}
  if(isset($input['description'])){$updates[]="description=?";$params[]=$input['description'];}
  if(!empty($updates)){$params[]=$cid;$d->query("UPDATE conversations SET ".implode(",",$updates)." WHERE id=?",$params);}
  echo json_encode(['success'=>true]);exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action']);
}catch(Throwable $e){
  echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid method']);
