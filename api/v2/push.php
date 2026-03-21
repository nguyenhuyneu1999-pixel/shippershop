<?php
/**
 * ShipperShop API v2 — Push Notifications
 * Subscribe, unsubscribe, send (admin)
 */
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

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Check subscription status
    if($action==='status'){
        if(!$uid) ok('OK',['subscribed'=>false]);
        $sub=$d->fetchOne("SELECT id,endpoint,created_at FROM push_subscriptions WHERE user_id=?",[$uid]);
        ok('OK',['subscribed'=>!!$sub,'endpoint'=>$sub?substr($sub['endpoint'],0,50).'...':null,'since'=>$sub?$sub['created_at']:null]);
    }

    // List subscriptions (admin)
    if($action==='list'){
        $uid=require_auth();
        $u=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$u||$u['role']!=='admin') fail('Admin only',403);
        $count=intval($d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c']);
        $recent=$d->fetchAll("SELECT ps.id,ps.user_id,u.fullname,ps.created_at FROM push_subscriptions ps LEFT JOIN users u ON ps.user_id=u.id ORDER BY ps.created_at DESC LIMIT 20");
        ok('OK',['total'=>$count,'recent'=>$recent]);
    }

    ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Subscribe
    if($action==='subscribe'){
        $endpoint=$input['endpoint']??'';
        $keys=$input['keys']??[];
        if(!$endpoint) fail('Missing endpoint');
        // Upsert
        $d->query("DELETE FROM push_subscriptions WHERE user_id=?",[$uid]);
        $pdo->prepare("INSERT INTO push_subscriptions (user_id,endpoint,p256dh,auth_key,created_at) VALUES (?,?,?,?,NOW())")->execute([
            $uid,$endpoint,$keys['p256dh']??'',$keys['auth']??''
        ]);
        ok('Đã đăng ký push');
    }

    // Unsubscribe
    if($action==='unsubscribe'){
        $d->query("DELETE FROM push_subscriptions WHERE user_id=?",[$uid]);
        ok('Đã hủy đăng ký');
    }

    // Send push (admin only)
    if($action==='send'){
        $u=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$u||$u['role']!=='admin') fail('Admin only',403);
        $title=$input['title']??'ShipperShop';
        $body=$input['body']??'';
        $url=$input['url']??'/';
        $targetUserId=intval($input['user_id']??0);

        if(!$body) fail('Missing body');

        // Get subscriptions
        if($targetUserId){
            $subs=$d->fetchAll("SELECT endpoint,p256dh,auth_key FROM push_subscriptions WHERE user_id=?",[$targetUserId]);
        }else{
            $subs=$d->fetchAll("SELECT endpoint,p256dh,auth_key FROM push_subscriptions LIMIT 1000");
        }

        // Note: actual Web Push requires VAPID keys + web-push library
        // This stores the intent; a cron job or queue worker would send them
        foreach($subs as $sub){
            try{
                $pdo->prepare("INSERT INTO email_queue (recipient,subject,body,`status`,created_at) VALUES (?,?,?,'pending',NOW())")->execute([
                    'push:'.$sub['endpoint'],
                    $title,
                    json_encode(['title'=>$title,'body'=>$body,'url'=>$url])
                ]);
            }catch(\Throwable $e){}
        }

        ok('Queued',['recipients'=>count($subs)]);
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
