<?php
/**
 * ShipperShop API v2 — Social (Follow, Friends, Online)
 */
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Followers of user X
    if($action==='followers'){
        $tid=intval($_GET['user_id']??0);$limit=min(intval($_GET['limit']??30),100);$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$limit;
        if(!$tid) fail('Missing user_id');
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$tid])['c']);
        $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_online,u.total_success FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=? AND u.`status`='active' ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset",[$tid]);
        if($uid&&$users){
            $uids=array_column($users,'id');$ph=implode(',',array_fill(0,count($uids),'?'));
            $myFollows=$d->fetchAll("SELECT following_id FROM follows WHERE follower_id=? AND following_id IN ($ph)",array_merge([$uid],$uids));
            $set=array_flip(array_column($myFollows,'following_id'));
            foreach($users as &$u){$u['i_follow']=isset($set[$u['id']]);}unset($u);
        }
        echo json_encode(['success'=>true,'data'=>['users'=>$users,'meta'=>['total'=>$total,'page'=>$page]]]);exit;
    }

    // Following of user X
    if($action==='following'){
        $tid=intval($_GET['user_id']??0);$limit=min(intval($_GET['limit']??30),100);$page=max(1,intval($_GET['page']??1));$offset=($page-1)*$limit;
        if(!$tid) fail('Missing user_id');
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$tid])['c']);
        $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_online,u.total_success FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=? AND u.`status`='active' ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset",[$tid]);
        if($uid&&$users){
            $uids=array_column($users,'id');$ph=implode(',',array_fill(0,count($uids),'?'));
            $myFollows=$d->fetchAll("SELECT following_id FROM follows WHERE follower_id=? AND following_id IN ($ph)",array_merge([$uid],$uids));
            $set=array_flip(array_column($myFollows,'following_id'));
            foreach($users as &$u){$u['i_follow']=isset($set[$u['id']]);}unset($u);
        }
        echo json_encode(['success'=>true,'data'=>['users'=>$users,'meta'=>['total'=>$total,'page'=>$page]]]);exit;
    }

    // Mutual follows (friends)
    if($action==='friends'){
        if(!$uid) fail('Auth required',401);
        $limit=min(intval($_GET['limit']??30),100);
        $friends=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_online,u.total_success FROM follows f1 JOIN follows f2 ON f1.follower_id=f2.following_id AND f1.following_id=f2.follower_id JOIN users u ON f1.following_id=u.id WHERE f1.follower_id=? AND u.`status`='active' ORDER BY u.is_online DESC, u.fullname LIMIT $limit",[$uid]);
        ok('OK',$friends);
    }

    // Online users
    if($action==='online'){
        $limit=min(intval($_GET['limit']??20),50);
        $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE is_online=1 AND `status`='active' ORDER BY last_active DESC LIMIT $limit");
        ok('OK',$users);
    }

    // Suggestions (people you might want to follow)
    if($action==='suggestions'){
        if(!$uid) fail('Auth required',401);
        $limit=min(intval($_GET['limit']??10),30);
        // Users with same shipping_company, or popular, not already followed
        $user=$d->fetchOne("SELECT shipping_company FROM users WHERE id=?",[$uid]);
        $company=$user['shipping_company']??'';
        $suggestions=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.total_success,u.total_posts,(SELECT COUNT(*) FROM follows WHERE following_id=u.id) as followers_count FROM users u WHERE u.id!=? AND u.`status`='active' AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=?) ORDER BY CASE WHEN u.shipping_company=? AND ?!='' THEN 0 ELSE 1 END, u.total_success DESC, followers_count DESC LIMIT $limit",[$uid,$uid,$company,$company]);
        ok('OK',$suggestions);
    }

    // Check follow status
    if($action==='check'){
        if(!$uid) fail('Auth required',401);
        $tid=intval($_GET['user_id']??0);
        $iFollow=!!$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
        $followsMe=!!$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$tid,$uid]);
        ok('OK',['i_follow'=>$iFollow,'follows_me'=>$followsMe,'mutual'=>$iFollow&&$followsMe]);
    }

    ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Follow/unfollow toggle
    if($action==='follow'){
        rate_enforce('follow',30,3600);
        $tid=intval($input['user_id']??0);
        if(!$tid||$tid===$uid) fail('Invalid user');
        if(!$d->fetchOne("SELECT id FROM users WHERE id=? AND `status`='active'",[$tid])) fail('User not found',404);

        $ex=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
        if($ex){
            $d->query("DELETE FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
            ok('Đã bỏ theo dõi',['following'=>false]);
        }else{
            $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$tid]);
            // Notification
            try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'follow','Người theo dõi mới',?,?,NOW())")->execute([$tid,getUserName($uid).' đã theo dõi bạn',json_encode(['user_id'=>$uid])]);}catch(\Throwable $e){}
            // XP
            try{$pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,'follow_received',2,'Được theo dõi bởi user #'.$uid,NOW())")->execute([$tid]);}catch(\Throwable $e){}
            ok('Đã theo dõi!',['following'=>true]);
        }
    }

    fail('Action không hợp lệ');
}

// Block/Unblock
if($method==='POST'&&$action==='block'){
    $uid=require_auth();
    $tid=intval($input['user_id']??0);
    if(!$tid||$tid===$uid) fail('Invalid user');
    $exists=$d->fetchOne("SELECT id FROM user_blocks WHERE user_id=? AND blocked_id=?",[$uid,$tid]);
    if($exists){
        $d->query("DELETE FROM user_blocks WHERE user_id=? AND blocked_id=?",[$uid,$tid]);
        ok('Đã bỏ chặn',['blocked'=>false]);
    }else{
        $pdo->prepare("INSERT IGNORE INTO user_blocks (user_id,blocked_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$tid]);
        // Also unfollow both ways
        $d->query("DELETE FROM follows WHERE (follower_id=? AND following_id=?) OR (follower_id=? AND following_id=?)",[$uid,$tid,$tid,$uid]);
        ok('Đã chặn user',['blocked'=>true]);
    }
}

// Block list
if($method==='GET'&&$action==='blocked'){
    $uid=require_auth();
    $blocked=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,ub.created_at as blocked_at FROM user_blocks ub JOIN users u ON ub.blocked_id=u.id WHERE ub.user_id=? ORDER BY ub.created_at DESC",[$uid]);
    ok('OK',$blocked);
}

// Check if blocked
if($method==='GET'&&$action==='is_blocked'){
    $uid=optional_auth();
    $tid=intval($_GET['user_id']??0);
    if(!$uid||!$tid){ok('OK',['blocked'=>false,'blocked_by'=>false]);}
    $blocked=!!$d->fetchOne("SELECT id FROM user_blocks WHERE user_id=? AND blocked_id=?",[$uid,$tid]);
    $blockedBy=!!$d->fetchOne("SELECT id FROM user_blocks WHERE user_id=? AND blocked_id=?",[$tid,$uid]);
    ok('OK',['blocked'=>$blocked,'blocked_by'=>$blockedBy]);
}

fail('Method không hỗ trợ',405);

function getUserName($uid){
    $u=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
    return $u?$u['fullname']:'User';
}
