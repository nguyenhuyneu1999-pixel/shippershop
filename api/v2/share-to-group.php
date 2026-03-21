<?php
// ShipperShop API v2 — Share Post to Group
// Cross-post a post into a group as a shared reference
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

function sg_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sg_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='POST'){
    rate_enforce('share_to_group',10,3600);
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    $groupId=intval($input['group_id']??0);
    $comment=trim($input['comment']??'');

    if(!$postId||!$groupId) sg_fail('Missing post_id or group_id');

    // Check post exists
    $post=$d->fetchOne("SELECT id,content,user_id FROM posts WHERE id=? AND `status`='active'",[$postId]);
    if(!$post) sg_fail('Bài viết không tồn tại',404);

    // Check group membership
    $member=$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$groupId,$uid]);
    if(!$member) sg_fail('Bạn chưa tham gia nhóm này',403);

    // Create group post with shared reference
    $sharedContent=($comment?$comment."\n\n":'').'[Chia sẻ từ bài viết #'.$postId.']'."\n".mb_substr($post['content']??'',0,200);
    $pdo->prepare("INSERT INTO group_posts (group_id,user_id,content,`status`,created_at) VALUES (?,?,?,'active',NOW())")->execute([$groupId,$uid,$sharedContent]);
    $gpId=intval($pdo->lastInsertId());
    if(!$gpId){$r=$pdo->query("SELECT MAX(id) as m FROM group_posts");$gpId=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

    // Increment share count on original post
    $d->query("UPDATE posts SET shares_count=COALESCE(shares_count,0)+1 WHERE id=?",[$postId]);

    // Notify original author
    if(intval($post['user_id'])!==$uid){
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'share','Bài được chia sẻ',?,?,NOW())")->execute([intval($post['user_id']),getUserName($uid).' đã chia sẻ bài của bạn vào nhóm',json_encode(['post_id'=>$postId,'group_id'=>$groupId,'user_id'=>$uid])]);}catch(\Throwable $e){}
    }

    sg_ok('Đã chia sẻ vào nhóm!',['group_post_id'=>$gpId]);
}

// GET: list groups user can share to
if($_SERVER['REQUEST_METHOD']==='GET'){
    $groups=$d->fetchAll("SELECT g.id,g.name,g.avatar FROM group_members gm JOIN `groups` g ON gm.group_id=g.id WHERE gm.user_id=? ORDER BY g.name",[$uid]);
    sg_ok('OK',$groups);
}

sg_fail('Method không hỗ trợ',405);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}

function getUserName($uid){$u=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);return $u?$u['fullname']:'User';}
