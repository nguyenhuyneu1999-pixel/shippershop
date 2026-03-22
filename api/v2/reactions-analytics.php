<?php
// ShipperShop API v2 — Reactions Analytics
// Breakdown of reactions across posts: which emoji used most, trends
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$action=$_GET['action']??'';

function ra_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$days=min(intval($_GET['days']??30),365);

if(!$action||$action==='overview'){
    $data=cache_remember('reactions_analytics_'.$days, function() use($d,$days) {
        // Total reactions from post_likes
        $totalLikes=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);

        // Daily trend
        $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");

        // Most liked posts
        $topPosts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) ORDER BY p.likes_count DESC LIMIT 5");

        // Most active likers
        $topLikers=$d->fetchAll("SELECT pl.user_id,u.fullname,u.avatar,COUNT(*) as like_count FROM post_likes pl JOIN users u ON pl.user_id=u.id WHERE pl.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY pl.user_id ORDER BY like_count DESC LIMIT 10");

        // Average likes per post
        $avgLikes=floatval($d->fetchOne("SELECT AVG(likes_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['a']??0);

        // Likes by hour
        $byHour=$d->fetchAll("SELECT HOUR(created_at) as h,COUNT(*) as c FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY HOUR(created_at) ORDER BY c DESC");

        return ['total_likes'=>$totalLikes,'avg_per_post'=>round($avgLikes,1),'daily'=>$daily,'top_posts'=>$topPosts,'top_likers'=>$topLikers,'by_hour'=>$byHour,'period_days'=>$days];
    }, 600);
    ra_ok('OK',$data);
}

// User's reaction stats
if($action==='user'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) ra_ok('OK',['given'=>0,'received'=>0]);
    $given=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$userId])['c']);
    $received=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$userId])['s']);
    ra_ok('OK',['given'=>$given,'received'=>$received,'ratio'=>$given>0?round($received/$given,2):0]);
}

ra_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
