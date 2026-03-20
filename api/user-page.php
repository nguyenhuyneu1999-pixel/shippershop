<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);
$d=db();$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';$targetId=intval($_GET['id']??0);
try {
if($method==='GET'){
  $viewerId=getOptionalAuthUserId();
  if($action===''||$action==='profile'){
    if(!$targetId){echo json_encode(['success'=>false,'message'=>'Missing id']);exit;}
    $user=$d->fetchOne("SELECT id,fullname,username,avatar,cover_image,bio,shipping_company,address,created_at FROM users WHERE id=?",[$targetId]);
    if(!$user){echo json_encode(['success'=>false,'message'=>'Not found']);exit;}
    $pc=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND status='active'",[$targetId]);
    $gpc=$d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE user_id=? AND status='active'",[$targetId]);
    $lk=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as c FROM posts WHERE user_id=? AND status='active'",[$targetId]);
    $glk=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as c FROM group_posts WHERE user_id=? AND status='active'",[$targetId]);
    $fl=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$targetId]);
    $fg=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$targetId]);
    $user['post_count']=intval($pc['c']??0)+intval($gpc['c']??0);
    $user['total_success']=intval($lk['c']??0)+intval($glk['c']??0);
    $user['follower_count']=intval($fl['c']??0);
    $user['following_count']=intval($fg['c']??0);
    $user['is_following']=false;$user['is_friend']=false;$user['friend_status']=null;
    $user['is_self']=(intval($viewerId)===intval($targetId)&&$viewerId>0);
    if($viewerId&&$viewerId!==$targetId){
      $fw=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$viewerId,$targetId]);
      $user['is_following']=!!$fw;
    }
    $created=new DateTime($user['created_at']);$now=new DateTime();
    $user['account_age_days']=$now->diff($created)->days;
    echo json_encode(['success'=>true,'data'=>$user]);exit;
  }
  if($action==='posts'){
    // Get posts with user_liked and user_saved
    $sql = "SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.username as user_username, u.shipping_company, (SELECT COUNT(*) FROM post_likes WHERE post_id=p.id) as likes_count, (SELECT COUNT(*) FROM comments WHERE post_id=p.id AND status='active') as comments_count";
    if ($viewerId) {
      $sql .= ", (SELECT COUNT(*) FROM likes WHERE post_id=p.id AND user_id=?) as user_liked";
      $sql .= ", (SELECT COUNT(*) FROM saved_posts WHERE post_id=p.id AND user_id=?) as user_saved";
    }
    $sql .= " FROM posts p JOIN users u ON p.user_id=u.id WHERE p.user_id=? AND p.status='active' ORDER BY p.created_at DESC LIMIT 20";
    
    $params = [];
    if ($viewerId) {
      $params[] = $viewerId;
      $params[] = $viewerId;
    }
    $params[] = $targetId;
    
    $posts = $d->fetchAll($sql, $params);
    
    // Convert to boolean
    foreach ($posts as &$p) {
      $p['user_liked'] = isset($p['user_liked']) ? (bool)$p['user_liked'] : false;
      $p['user_saved'] = isset($p['user_saved']) ? (bool)$p['user_saved'] : false;
    }
    unset($p);
    echo json_encode(['success'=>true,'data'=>$posts]);exit;
  }
  if($action==='comments'){
    $cmts=$d->fetchAll("SELECT c.*,p.content as post_content,p.id as post_id FROM comments c JOIN posts p ON c.post_id=p.id WHERE c.user_id=? AND c.status='active' ORDER BY c.created_at DESC LIMIT 30",[$targetId]);
    echo json_encode(['success'=>true,'data'=>$cmts]);exit;
  }
}
if($method==='POST'){
  $userId=getAuthUserId();$input=json_decode(file_get_contents('php://input'),true);
  if($action==='update_profile'){
    $bio=$input['bio']??null;$dn=$input['display_name']??null;$s=[];$p=[];
    if($bio!==null){$s[]="bio=?";$p[]=$bio;}
    if($dn!==null){$s[]="fullname=?";$p[]=$dn;}
    if($s){$p[]=$userId;$d->query("UPDATE users SET ".implode(',',$s)." WHERE id=?",$p);}
    echo json_encode(['success'=>true]);exit;
  }
  if($action==='upload_avatar'||$action==='upload_cover'){
    if(!$userId){echo json_encode(['success'=>false,'message'=>'Auth required']);exit;}
    if(empty($_FILES['image'])){echo json_encode(['success'=>false,'message'=>'No file']);exit;}
    $file=$_FILES['image'];
    $allowed=['image/jpeg','image/png','image/webp','image/gif'];
    if(!in_array($file['type'],$allowed)){echo json_encode(['success'=>false,'message'=>'Invalid type']);exit;}
    if($file['size']>5*1024*1024){echo json_encode(['success'=>false,'message'=>'Max 5MB']);exit;}
    $ext=pathinfo($file['name'],PATHINFO_EXTENSION)?:('jpg');
    $folder=$action==='upload_avatar'?'avatars':'covers';
    $dir=__DIR__.'/../uploads/'.$folder;
    if(!is_dir($dir))mkdir($dir,0755,true);
    $fname=$userId.'_'.time().'.'.$ext;
    $path=$dir.'/'.$fname;
    if(!move_uploaded_file($file['tmp_name'],$path)){echo json_encode(['success'=>false,'message'=>'Upload failed']);exit;}
    $url='/uploads/'.$folder.'/'.$fname;
    $col=$action==='upload_avatar'?'avatar':'cover_image';
    $d->query("UPDATE users SET $col=? WHERE id=?",[$url,$userId]);
    echo json_encode(['success'=>true,'data'=>['url'=>$url]]);exit;
  }
}
} catch(Throwable $e){echo json_encode(['success'=>false,'message'=>$e->getMessage()]);exit;}
echo json_encode(['success'=>false,'message'=>'Invalid']);
