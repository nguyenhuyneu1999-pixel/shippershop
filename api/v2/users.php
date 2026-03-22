<?php
/**
 * ShipperShop API v2 — Users & Social
 * Profile, follow, block, settings, sessions
 */
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';
require_once __DIR__.'/../../includes/rate-limiter.php';
require_once __DIR__.'/../../includes/validator.php';
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

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg],JSON_UNESCAPED_UNICODE);exit;}

// ========== GET ==========
if($method==='GET'){

    // --- Public profile ---
    if($action==='profile'){
        $tid=intval($_GET['id']??0);
        if(!$tid) fail('Missing id');
        $uid=optional_auth();

        $cacheKey='user_profile_'.$tid;
        $user=cache_get($cacheKey);
        if(!$user){
            $user=$d->fetchOne("SELECT id,fullname,username,email,avatar,cover_image,bio,shipping_company,role,total_success,total_posts,created_at FROM users WHERE id=? AND `status`='active'",[$tid]);
            if(!$user) fail('Không tìm thấy',404);

            // Counts
            $fc=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$tid]);
            $fg=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$tid]);
            $user['followers_count']=intval($fc['c']??0);
            $user['following_count']=intval($fg['c']??0);

            // Subscription
            $sub=$d->fetchOne("SELECT sp.name as plan_name,sp.badge,us.expires_at FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.expires_at>NOW() ORDER BY us.id DESC LIMIT 1",[$tid]);
            $user['subscription']=$sub?:null;

            // Groups count
            $gc=$d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$tid]);
            $user['groups_count']=intval($gc['c']??0);

            cache_set($cacheKey,$user,300);
        }

        $user['is_self']=($uid&&$uid===$tid);
        if($uid&&$uid!==$tid){
            $user['is_following']=!!$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
            $user['is_blocked']=!!$d->fetchOne("SELECT id FROM user_blocks WHERE user_id=? AND blocked_user_id=?",[$uid,$tid]);
            $user['is_blocked_by']=!!$d->fetchOne("SELECT id FROM user_blocks WHERE user_id=? AND blocked_user_id=?",[$tid,$uid]);
        }else{
            $user['is_following']=false;$user['is_blocked']=false;$user['is_blocked_by']=false;
        }
        // Remove email for non-self
        if(!$user['is_self']) unset($user['email']);

        ok('OK',$user);
    }

    // --- Current user (me) ---
    if($action==='me'){
        $uid=require_auth();
        $user=$d->fetchOne("SELECT id,fullname,username,email,avatar,cover_image,bio,shipping_company,phone,role,total_success,total_posts,settings,created_at FROM users WHERE id=?",[$uid]);
        if(!$user) fail('Not found',404);
        $sub=$d->fetchOne("SELECT sp.name as plan_name,sp.badge,us.expires_at,us.auto_renew FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.expires_at>NOW() ORDER BY us.id DESC LIMIT 1",[$uid]);
        $user['subscription']=$sub?:null;
        $wallet=$d->fetchOne("SELECT balance FROM wallets WHERE user_id=?",[$uid]);
        $user['wallet_balance']=floatval($wallet['balance']??0);
        $user['settings']=$user['settings']?json_decode($user['settings'],true):[];
        ok('OK',$user);
    }

    // --- Followers ---
    if($action==='followers'){
        $tid=intval($_GET['user_id']??0);
        $page=max(1,intval($_GET['page']??1));
        $limit=min(intval($_GET['limit']??20),50);
        $offset=($page-1)*$limit;
        $uid=optional_auth();

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$tid])['c']);
        $rows=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.shipping_company,u.is_online FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=? ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset",[$tid]);

        // Batch check if current user follows them
        if($uid&&$rows){
            $ids=array_column($rows,'id');
            $ph=implode(',',array_fill(0,count($ids),'?'));
            $fol=$d->fetchAll("SELECT following_id FROM follows WHERE follower_id=? AND following_id IN ($ph)",array_merge([$uid],$ids));
            $folSet=array_flip(array_column($fol,'following_id'));
            foreach($rows as &$r){$r['i_follow']=isset($folSet[$r['id']]);}unset($r);
        }

        echo json_encode(['success'=>true,'data'=>['users'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // --- Following ---
    if($action==='following'){
        $tid=intval($_GET['user_id']??0);
        $page=max(1,intval($_GET['page']??1));
        $limit=min(intval($_GET['limit']??20),50);
        $offset=($page-1)*$limit;

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$tid])['c']);
        $rows=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.shipping_company,u.is_online FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=? ORDER BY f.created_at DESC LIMIT $limit OFFSET $offset",[$tid]);
        echo json_encode(['success'=>true,'data'=>['users'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // --- Blocked list ---
    if($action==='blocked'){
        $uid=require_auth();
        $rows=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,ub.created_at as blocked_at FROM user_blocks ub JOIN users u ON ub.blocked_user_id=u.id WHERE ub.user_id=? ORDER BY ub.created_at DESC",[$uid]);
        ok('OK',$rows);
    }

    // --- Suggestions ---
    if($action==='suggestions'){
        $uid=optional_auth();
        $limit=min(intval($_GET['limit']??10),20);
        if(!$uid){
            $users=$d->fetchAll("SELECT id,fullname,avatar,username,shipping_company,total_success FROM users WHERE `status`='active' ORDER BY total_success DESC LIMIT $limit");
            ok('OK',$users);
        }
        // Suggest: same shipping company, or popular, not already followed, not blocked
        $users=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.username,u.shipping_company,u.total_success,u.bio FROM users u WHERE u.`status`='active' AND u.id!=? AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id=?) AND u.id NOT IN (SELECT blocked_user_id FROM user_blocks WHERE user_id=?) ORDER BY (u.shipping_company = (SELECT shipping_company FROM users WHERE id=?)) DESC, u.total_success DESC LIMIT $limit",[$uid,$uid,$uid,$uid]);
        ok('OK',$users);
    }

    // --- Sessions ---
    if($action==='sessions'){
        $uid=require_auth();
        $rows=$d->fetchAll("SELECT id,ip_address,user_agent,last_activity FROM user_sessions WHERE user_id=? ORDER BY last_activity DESC LIMIT 20",[$uid]);
        ok('OK',$rows);
    }

    // --- Settings ---
    if($action==='settings'){
        $uid=require_auth();
        $user=$d->fetchOne("SELECT settings FROM users WHERE id=?",[$uid]);
        $settings=$user&&$user['settings']?json_decode($user['settings'],true):[];
        // Default settings
        $defaults=['notif_likes'=>true,'notif_comments'=>true,'notif_follows'=>true,'notif_messages'=>true,'notif_groups'=>true,'notif_email'=>false,'profile_public'=>true];
        foreach($defaults as $k=>$v){if(!isset($settings[$k]))$settings[$k]=$v;}
        ok('OK',$settings);
    }

    // --- Search users ---
    if($action==='search'){
        $q=trim($_GET['q']??'');
        if(mb_strlen($q)<2) ok('OK',[]);
        $limit=min(intval($_GET['limit']??10),20);
        $users=$d->fetchAll("SELECT id,fullname,avatar,username,shipping_company,bio,total_success FROM users WHERE `status`='active' AND (fullname LIKE ? OR username LIKE ? OR shipping_company LIKE ?) ORDER BY total_success DESC LIMIT $limit",['%'.$q.'%','%'.$q.'%','%'.$q.'%']);
        ok('OK',$users);
    }

    ok('OK',[]);
}

// ========== POST ==========
if($method==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // === UPDATE PROFILE ===
    if($action==='update_profile'){
        $fields=[];$params=[];
        if(isset($input['fullname'])&&mb_strlen(trim($input['fullname']))>=2){$fields[]="fullname=?";$params[]=trim($input['fullname']);}
        if(isset($input['bio'])){$fields[]="bio=?";$params[]=mb_substr(trim($input['bio']),0,500);}
        if(isset($input['shipping_company'])){$fields[]="shipping_company=?";$params[]=trim($input['shipping_company']);}
        if(isset($input['phone'])){$fields[]="phone=?";$params[]=trim($input['phone']);}
        if(isset($input['address'])){$fields[]="address=?";$params[]=trim($input['address']);}
        if(isset($input['username'])){
            $un=trim($input['username']);
            if(mb_strlen($un)>=3){
                $ex=$d->fetchOne("SELECT id FROM users WHERE username=? AND id!=?",[$un,$uid]);
                if($ex) fail('Username đã tồn tại');
                $fields[]="username=?";$params[]=$un;
            }
        }
        if(empty($fields)) fail('Không có thông tin để cập nhật');
        $params[]=$uid;
        $d->query("UPDATE users SET ".implode(',',$fields)." WHERE id=?",$params);
        cache_del('user_profile_'.$uid);
        ok('Đã cập nhật!');
    }

    // === UPLOAD AVATAR ===
    if($action==='upload_avatar'){
        if(empty($_FILES['avatar'])) fail('Chọn ảnh');
        $up=handle_upload($_FILES['avatar'],'avatars',['user_id'=>$uid,'resize_max'=>500]);
        if(!$up['success']) fail($up['error']);
        $d->query("UPDATE users SET avatar=? WHERE id=?",[$up['url'],$uid]);
        cache_del('user_profile_'.$uid);
        ok('OK',['avatar'=>$up['url']]);
    }

    // === UPLOAD COVER ===
    if($action==='upload_cover'){
        if(empty($_FILES['cover'])) fail('Chọn ảnh');
        $up=handle_upload($_FILES['cover'],'covers',['user_id'=>$uid,'resize_max'=>1920]);
        if(!$up['success']) fail($up['error']);
        $d->query("UPDATE users SET cover_image=? WHERE id=?",[$up['url'],$uid]);
        cache_del('user_profile_'.$uid);
        ok('OK',['cover_image'=>$up['url']]);
    }

    // === FOLLOW / UNFOLLOW ===
    if($action==='follow'){
        $tid=intval($input['user_id']??0);
        if(!$tid||$tid===$uid) fail('Invalid user');
        if(!!$d->fetchOne("SELECT id FROM user_blocks WHERE (user_id=? AND blocked_user_id=?) OR (user_id=? AND blocked_user_id=?)",[$uid,$tid,$tid,$uid])) fail('Đã bị chặn');
        $ex=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
        if($ex){
            $d->query("DELETE FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
            ok('OK',['following'=>false]);
        }else{
            $pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$tid]);
            // Notification
            try{
                $me=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
                $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'follow',?,?,?,NOW())")->execute([$tid,'Theo dõi mới',($me?$me['fullname']:'Ai đó').' đã theo dõi bạn',json_encode(['link'=>'/user.html?id='.$uid])]);
            }catch(\Throwable $e){}
            ok('OK',['following'=>true]);
        }
    }

    // === BLOCK / UNBLOCK ===
    if($action==='block'){
        $tid=intval($input['user_id']??0);
        if(!$tid||$tid===$uid) fail('Invalid user');
        $ex=$d->fetchOne("SELECT id FROM user_blocks WHERE user_id=? AND blocked_user_id=?",[$uid,$tid]);
        if($ex) fail('Đã chặn rồi');
        $pdo->prepare("INSERT INTO user_blocks (user_id,blocked_user_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$tid]);
        // Auto-unfollow both directions
        $d->query("DELETE FROM follows WHERE (follower_id=? AND following_id=?) OR (follower_id=? AND following_id=?)",[$uid,$tid,$tid,$uid]);
        cache_del('user_profile_'.$uid);
        cache_del('user_profile_'.$tid);
        ok('Đã chặn');
    }
    if($action==='unblock'){
        $tid=intval($input['user_id']??0);
        $d->query("DELETE FROM user_blocks WHERE user_id=? AND blocked_user_id=?",[$uid,$tid]);
        ok('Đã bỏ chặn');
    }

    // === UPDATE SETTINGS ===
    if($action==='update_settings'){
        $current=$d->fetchOne("SELECT settings FROM users WHERE id=?",[$uid]);
        $settings=$current&&$current['settings']?json_decode($current['settings'],true):[];
        // Merge input
        $allowed=['notif_likes','notif_comments','notif_follows','notif_messages','notif_groups','notif_email','profile_public'];
        foreach($allowed as $k){
            if(isset($input[$k])) $settings[$k]=!!$input[$k];
        }
        $d->query("UPDATE users SET settings=? WHERE id=?",[json_encode($settings),$uid]);
        ok('OK',$settings);
    }

    // === DELETE ACCOUNT (soft) ===
    if($action==='delete_account'){
        $pw=trim($input['password']??'');
        if(!$pw) fail('Nhập mật khẩu để xác nhận');
        $user=$d->fetchOne("SELECT password FROM users WHERE id=?",[$uid]);
        if(!$user||!password_verify($pw,$user['password'])) fail('Mật khẩu không đúng');
        $d->query("UPDATE users SET `status`='deleted',banned_until='9999-12-31 23:59:59',ban_reason='Tự xóa tài khoản' WHERE id=?",[$uid]);
        ok('Tài khoản đã bị xóa. Liên hệ admin để khôi phục.');
    }

    // === TERMINATE SESSION ===
    if($action==='terminate_session'){
        $sid=trim($input['session_id']??'');
        if($sid) $d->query("DELETE FROM user_sessions WHERE id=? AND user_id=?",[$sid,$uid]);
        ok('OK');
    }

    fail('Action không hợp lệ');
}

fail('Method không hỗ trợ',405);
