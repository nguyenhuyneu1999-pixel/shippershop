<?php
// ShipperShop API v2 — User Personal Dashboard
// Aggregated personal stats: today, week, goals, challenges, rank
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function ud_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

$data=cache_remember('user_dash_'.$uid, function() use($d,$uid) {
    $u=$d->fetchOne("SELECT fullname,avatar,total_posts,total_success FROM users WHERE id=?",[$uid]);
    $todayPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= CURDATE()",[$uid])['c']);
    $weekPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c']);
    $todayLikes=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id=p.id WHERE p.user_id=? AND DATE(pl.created_at)=CURDATE()",[$uid])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$uid])['s']);
    $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$uid])['current_streak']??0);
    $level=max(1,floor($xp/100)+1);
    $unreadMsgs=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?) AND sender_id!=? AND is_read=0",[$uid,$uid])['c']);

    // Rank (by total posts)
    $rank=intval($d->fetchOne("SELECT COUNT(*)+1 as r FROM users WHERE `status`='active' AND total_posts > (SELECT total_posts FROM users WHERE id=?)",[$uid])['r']);

    return ['user'=>$u,'today'=>['posts'=>$todayPosts,'likes_received'=>$todayLikes],'week_posts'=>$weekPosts,'followers'=>$followers,'xp'=>$xp,'level'=>$level,'streak'=>$streak,'unread_messages'=>$unreadMsgs,'rank'=>$rank];
}, 120);

ud_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
