<?php
// ShipperShop API v2 — Admin Retention Dashboard
// User retention metrics: DAU/WAU/MAU ratio, churn rate, retention cohorts
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

function rd2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rd2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') rd2_fail('Admin only',403);

$data=cache_remember('retention_dashboard', function() use($d) {
    $dau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= CURDATE()")['c']);
    $wau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $mau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);

    $dauWauRatio=$wau>0?round($dau/$wau*100,1):0;
    $wauMauRatio=$mau>0?round($wau/$mau*100,1):0;
    $stickiness=$mau>0?round($dau/$mau*100,1):0;

    // New vs returning this week
    $newThisWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $returningThisWeek=intval($d->fetchOne("SELECT COUNT(DISTINCT p.user_id) as c FROM posts p JOIN users u ON p.user_id=u.id WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND u.created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);

    // Churn: users active 30-60 days ago but NOT active in last 30 days
    $churned=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY) AND user_id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))")['c']);
    $churnRate=$mau>0?round($churned/($mau+$churned)*100,1):0;

    // Weekly DAU trend
    $dauTrend=[];
    for($w=6;$w>=0;$w--){
        $day=date('Y-m-d',strtotime("-$w days"));
        $c=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE DATE(created_at)=?",[$day])['c']);
        $dauTrend[]=['day'=>$day,'dau'=>$c];
    }

    return ['dau'=>$dau,'wau'=>$wau,'mau'=>$mau,'dau_wau'=>$dauWauRatio,'wau_mau'=>$wauMauRatio,'stickiness'=>$stickiness,'new_week'=>$newThisWeek,'returning_week'=>$returningThisWeek,'churned'=>$churned,'churn_rate'=>$churnRate,'dau_trend'=>$dauTrend];
}, 600);

rd2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
