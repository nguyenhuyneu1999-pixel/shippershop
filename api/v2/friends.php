<?php
// ShipperShop API v2 — Friends (mutual follows + friend requests)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function fr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fr_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Mutual friends (both follow each other)
    if($action==='mutual'||!$action){
        if(!$uid) fr_fail('Auth required',401);
        $limit=min(intval($_GET['limit']??30),100);
        $search=trim($_GET['search']??'');
        $w='';$p=[$uid];
        if($search){$w=" AND u.fullname LIKE ?";$p[]='%'.$search.'%';}
        $friends=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_online,u.total_success,u.total_posts FROM follows f1 JOIN follows f2 ON f1.follower_id=f2.following_id AND f1.following_id=f2.follower_id JOIN users u ON f1.following_id=u.id WHERE f1.follower_id=? AND u.`status`='active'$w ORDER BY u.is_online DESC,u.fullname LIMIT $limit",$p);
        fr_ok('OK',$friends);
    }

    // Friends count
    if($action==='count'){
        $tid=intval($_GET['user_id']??($uid??0));
        if(!$tid) fr_fail('Missing user');
        $count=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows f1 JOIN follows f2 ON f1.follower_id=f2.following_id AND f1.following_id=f2.follower_id WHERE f1.follower_id=?",[$tid])['c']);
        fr_ok('OK',['count'=>$count]);
    }

    // Common friends between current user and target
    if($action==='common'){
        if(!$uid) fr_fail('Auth required',401);
        $tid=intval($_GET['user_id']??0);
        if(!$tid||$tid===$uid) fr_fail('Invalid user');
        $common=$d->fetchAll("SELECT u.id,u.fullname,u.avatar FROM follows a JOIN follows b ON a.following_id=b.following_id JOIN users u ON a.following_id=u.id WHERE a.follower_id=? AND b.follower_id=? AND a.following_id!=? AND a.following_id!=? AND u.`status`='active' LIMIT 20",[$uid,$tid,$uid,$tid]);
        fr_ok('OK',$common);
    }

    // Pending friend requests (people who follow me but I don't follow back)
    if($action==='pending'){
        if(!$uid) fr_fail('Auth required',401);
        $pending=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,f.created_at as followed_at FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=? AND f.follower_id NOT IN (SELECT following_id FROM follows WHERE follower_id=?) AND u.`status`='active' ORDER BY f.created_at DESC LIMIT 30",[$uid,$uid]);
        fr_ok('OK',$pending);
    }

    fr_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Accept (follow back)
    if($action==='accept'){
        $tid=intval($input['user_id']??0);
        if(!$tid) fr_fail('Missing user');
        $ex=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
        if(!$ex){
            $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$tid]);
            // Notify
            try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'follow','Người theo dõi mới',?,?,NOW())")->execute([$tid,'Đã theo dõi lại bạn',json_encode(['user_id'=>$uid])]);}catch(\Throwable $e){}
        }
        fr_ok('Đã theo dõi lại!',['mutual'=>true]);
    }

    fr_fail('Action không hợp lệ');
}
fr_fail('Method không hỗ trợ',405);
