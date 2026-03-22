<?php
// ShipperShop API v2 — User Daily Report
// Personal daily summary: posts, likes received, deliveries, XP earned
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

$d=db();

function dr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$date=$_GET['date']??date('Y-m-d');

$data=cache_remember('daily_report_'.$uid.'_'.$date, function() use($d,$uid,$date) {
    $posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND DATE(created_at)=?",[$uid,$date])['c']);
    $likesReceived=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes pl JOIN posts p ON pl.post_id=p.id WHERE p.user_id=? AND DATE(pl.created_at)=?",[$uid,$date])['c']);
    $likesGiven=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE user_id=? AND DATE(created_at)=?",[$uid,$date])['c']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND DATE(created_at)=?",[$uid,$date])['c']);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=? AND DATE(created_at)=?",[$uid,$date])['s']);
    $messages=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=? AND DATE(created_at)=?",[$uid,$date])['c']);
    $newFollowers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND DATE(created_at)=?",[$uid,$date])['c']);

    // Score (gamified daily performance)
    $score=$posts*10+$likesReceived*2+$comments*3+$xp+$messages+$newFollowers*5;
    $grade=$score>=50?'A':($score>=30?'B':($score>=15?'C':($score>=5?'D':'F')));

    // Streak check
    $streak=$d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$uid]);

    return ['date'=>$date,'posts'=>$posts,'likes_received'=>$likesReceived,'likes_given'=>$likesGiven,'comments'=>$comments,'xp_earned'=>$xp,'messages'=>$messages,'new_followers'=>$newFollowers,'score'=>$score,'grade'=>$grade,'streak'=>intval($streak['current_streak']??0)];
}, 300);

dr_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
