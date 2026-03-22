<?php
// ShipperShop API v2 — Achievements Wall
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function aw2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

if(!$action||$action==='leaderboard'){
    $top=$d->fetchAll("SELECT ux.user_id,u.fullname,u.avatar,u.shipping_company,SUM(ux.xp) as total_xp FROM user_xp ux JOIN users u ON ux.user_id=u.id WHERE u.`status`='active' GROUP BY ux.user_id ORDER BY total_xp DESC LIMIT 20");
    aw2_ok('OK',$top);
}

if($action==='recent'){
    $recent=$d->fetchAll("SELECT ux.user_id,u.fullname,u.avatar,ux.xp,ux.reason,ux.created_at FROM user_xp ux JOIN users u ON ux.user_id=u.id ORDER BY ux.created_at DESC LIMIT 20");
    aw2_ok('OK',$recent);
}

if($action==='streaks'){
    $streaks=$d->fetchAll("SELECT us.user_id,u.fullname,u.avatar,us.current_streak,us.longest_streak FROM user_streaks us JOIN users u ON us.user_id=u.id WHERE u.`status`='active' AND us.current_streak>0 ORDER BY us.current_streak DESC LIMIT 20");
    aw2_ok('OK',$streaks);
}

if($action==='user'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) aw2_ok('OK',[]);

    $totalXp=0;$breakdown=[];$recent=[];$streakData=['current'=>0,'longest'=>0];$level=1;$xpInLevel=0;

    try{$totalXp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$userId])['s']);}catch(\Throwable $e){}
    try{$breakdown=$d->fetchAll("SELECT reason,SUM(xp) as xp,COUNT(*) as times FROM user_xp WHERE user_id=? GROUP BY reason ORDER BY xp DESC",[$userId]);}catch(\Throwable $e){}
    try{$recent=$d->fetchAll("SELECT xp,reason,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT 10",[$userId]);}catch(\Throwable $e){}
    try{$s=$d->fetchOne("SELECT current_streak,longest_streak FROM user_streaks WHERE user_id=?",[$userId]);if($s){$streakData=['current'=>intval($s['current_streak']),'longest'=>intval($s['longest_streak'])];}}catch(\Throwable $e){}

    $level=max(1,floor($totalXp/100)+1);
    $xpInLevel=$totalXp%100;

    aw2_ok('OK',['total_xp'=>$totalXp,'level'=>$level,'xp_in_level'=>$xpInLevel,'xp_to_next'=>100-$xpInLevel,'breakdown'=>$breakdown,'recent'=>$recent,'streak'=>$streakData]);
}

aw2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
