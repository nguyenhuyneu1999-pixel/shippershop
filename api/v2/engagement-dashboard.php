<?php
// ShipperShop API v2 — Admin Engagement Dashboard
// Platform engagement metrics: DAU, WAU, MAU, retention, engagement rate
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

function ed_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ed_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ed_fail('Admin only',403);

$data=cache_remember('engagement_dash', function() use($d) {
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $dau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= CURDATE()")['c']);
    $wau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $mau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);

    // Engagement rates
    $postsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
    $commentsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= CURDATE()")['c']);
    $likesToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= CURDATE()")['c']);

    // Content per user
    $avgPostsPerUser=$mau?round(intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'])/$mau,1):0;

    // Stickiness (DAU/MAU ratio)
    $stickiness=$mau?round($dau/$mau*100,1):0;

    // 7-day DAU trend
    $dauTrend=[];
    for($i=6;$i>=0;$i--){
        $date=date('Y-m-d',strtotime("-$i days"));
        $c=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE DATE(created_at)=?",[$date])['c']);
        $dauTrend[]=['date'=>$date,'dau'=>$c];
    }

    // Top engagement actions today
    $actions=['posts'=>$postsToday,'comments'=>$commentsToday,'likes'=>$likesToday];

    return ['dau'=>$dau,'wau'=>$wau,'mau'=>$mau,'total_users'=>$totalUsers,'stickiness'=>$stickiness,'avg_posts_per_user'=>$avgPostsPerUser,'today'=>$actions,'dau_trend'=>$dauTrend,'generated_at'=>date('c')];
}, 180);

ed_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
