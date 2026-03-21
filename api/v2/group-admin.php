<?php
// ShipperShop API v2 — Group Admin Tools
// Approve/reject posts, ban/promote members, group stats
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
$action=$_GET['action']??'';

function ga_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ga_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

function isGroupAdmin($d,$gid,$uid){
    $m=$d->fetchOne("SELECT role FROM group_members WHERE group_id=? AND user_id=?",[$gid,$uid]);
    return $m&&in_array($m['role'],['admin','moderator','owner']);
}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $gid=intval($_GET['group_id']??0);
    if(!$gid) ga_fail('Missing group_id');
    if(!isGroupAdmin($d,$gid,$uid)) ga_fail('Not group admin',403);

    // Group stats
    if(!$action||$action==='stats'){
        $memberCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE group_id=?",[$gid])['c']);
        $postCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE group_id=? AND `status`='active'",[$gid])['c']);
        $weekPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE group_id=? AND created_at>DATE_SUB(NOW(),INTERVAL 7 DAY)",[$gid])['c']);
        $topPosters=$d->fetchAll("SELECT gp.user_id,u.fullname,u.avatar,COUNT(*) as post_count FROM group_posts gp JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.created_at>DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY gp.user_id ORDER BY post_count DESC LIMIT 5",[$gid]);
        ga_ok('OK',['members'=>$memberCount,'posts'=>$postCount,'week_posts'=>$weekPosts,'top_posters'=>$topPosters]);
    }

    // Pending posts (if group has approval mode)
    if($action==='pending_posts'){
        $posts=$d->fetchAll("SELECT gp.*,u.fullname as user_name,u.avatar as user_avatar FROM group_posts gp LEFT JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.`status`='pending' ORDER BY gp.created_at DESC",[$gid]);
        ga_ok('OK',$posts);
    }

    // Banned members
    if($action==='banned'){
        $banned=$d->fetchAll("SELECT gm.*,u.fullname,u.avatar FROM group_members gm JOIN users u ON gm.user_id=u.id WHERE gm.group_id=? AND gm.role='banned'",[$gid]);
        ga_ok('OK',$banned);
    }

    ga_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $gid=intval($input['group_id']??0);
    if(!$gid) ga_fail('Missing group_id');
    if(!isGroupAdmin($d,$gid,$uid)) ga_fail('Not group admin',403);

    // Approve post
    if($action==='approve_post'){
        $pid=intval($input['post_id']??0);
        if(!$pid) ga_fail('Missing post_id');
        $d->query("UPDATE group_posts SET `status`='active' WHERE id=? AND group_id=?",[$pid,$gid]);
        ga_ok('Đã duyệt bài');
    }

    // Reject post
    if($action==='reject_post'){
        $pid=intval($input['post_id']??0);
        $d->query("UPDATE group_posts SET `status`='rejected' WHERE id=? AND group_id=?",[$pid,$gid]);
        ga_ok('Đã từ chối bài');
    }

    // Ban member
    if($action==='ban_member'){
        $targetId=intval($input['user_id']??0);
        if(!$targetId||$targetId===$uid) ga_fail('Invalid user');
        $d->query("UPDATE group_members SET role='banned' WHERE group_id=? AND user_id=?",[$gid,$targetId]);
        ga_ok('Đã cấm thành viên');
    }

    // Unban
    if($action==='unban_member'){
        $targetId=intval($input['user_id']??0);
        $d->query("UPDATE group_members SET role='member' WHERE group_id=? AND user_id=? AND role='banned'",[$gid,$targetId]);
        ga_ok('Đã bỏ cấm');
    }

    // Promote to moderator
    if($action==='promote'){
        $targetId=intval($input['user_id']??0);
        $role=$input['role']??'moderator';
        if(!in_array($role,['member','moderator','admin'])) ga_fail('Invalid role');
        $d->query("UPDATE group_members SET role=? WHERE group_id=? AND user_id=?",[$role,$gid,$targetId]);
        ga_ok('Đã cập nhật vai trò');
    }

    // Delete post
    if($action==='delete_post'){
        $pid=intval($input['post_id']??0);
        $d->query("UPDATE group_posts SET `status`='deleted' WHERE id=? AND group_id=?",[$pid,$gid]);
        ga_ok('Đã xóa bài');
    }

    ga_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
