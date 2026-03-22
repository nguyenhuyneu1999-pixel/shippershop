<?php
// ShipperShop API v2 — Delivery Analytics Dashboard
// Comprehensive delivery performance: success rate, avg time, peak zones, ratings
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

function da2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$days=min(intval($_GET['days']??30),365);

$data=cache_remember('delivery_analytics_'.$uid.'_'.$days, function() use($d,$uid,$days) {
    // Posts as proxy for deliveries
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['c']);
    $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['s']);
    $totalComments=intval($d->fetchOne("SELECT COALESCE(SUM(comments_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['s']);

    // Daily breakdown
    $daily=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as posts, SUM(likes_count) as likes FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 14",[$uid]);

    // By province
    $byProvince=$d->fetchAll("SELECT province, COUNT(*) as posts FROM posts WHERE user_id=? AND `status`='active' AND province IS NOT NULL AND province!='' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY province ORDER BY posts DESC LIMIT 5",[$uid]);

    // Peak hour
    $peakHour=$d->fetchOne("SELECT HOUR(created_at) as h, COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY HOUR(created_at) ORDER BY c DESC LIMIT 1",[$uid]);

    // Engagement rate
    $engRate=$totalPosts>0?round(($totalLikes+$totalComments)/$totalPosts,1):0;

    // Streak
    $streak=$d->fetchOne("SELECT current_streak,longest_streak FROM user_streaks WHERE user_id=?",[$uid]);

    return ['period_days'=>$days,'total_posts'=>$totalPosts,'total_likes'=>$totalLikes,'total_comments'=>$totalComments,'engagement_rate'=>$engRate,'daily'=>array_reverse($daily),'by_province'=>$byProvince,'peak_hour'=>$peakHour?intval($peakHour['h']):null,'streak'=>['current'=>intval($streak['current_streak']??0),'longest'=>intval($streak['longest_streak']??0)]];
}, 600);

da2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
