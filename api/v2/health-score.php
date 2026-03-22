<?php
// ShipperShop API v2 — Admin Platform Health Score
// Overall platform health: uptime, engagement, growth, quality
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

function hs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function hs_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') hs_fail('Admin only',403);

$data=cache_remember('platform_health', function() use($d) {
    $scores=[];

    // Growth (new users this week vs last week)
    $thisWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $lastWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $growthRate=$lastWeek>0?round(($thisWeek-$lastWeek)/$lastWeek*100,1):($thisWeek>0?100:0);
    $scores['growth']=min(100,max(0,50+$growthRate));

    // Engagement (DAU/total active)
    $dau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= CURDATE()")['c']);
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $engRate=$total>0?round($dau/$total*100,1):0;
    $scores['engagement']=min(100,$engRate*10);

    // Content (posts today vs avg)
    $today=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
    $avg7d=floatval($d->fetchOne("SELECT COUNT(*)/7 as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??0);
    $scores['content']=$avg7d>0?min(100,round($today/$avg7d*50)):($today>0?50:0);

    // Quality (avg engagement per post)
    $avgEng=floatval($d->fetchOne("SELECT AVG(likes_count+comments_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??0);
    $scores['quality']=min(100,round($avgEng*15));

    // Retention (WAU/MAU)
    $wau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $mau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $scores['retention']=$mau>0?round($wau/$mau*100):0;

    $overall=round(array_sum($scores)/count($scores));
    $grade=$overall>=80?'A':($overall>=60?'B':($overall>=40?'C':'D'));

    return ['overall'=>$overall,'grade'=>$grade,'scores'=>$scores,'metrics'=>['growth_rate'=>$growthRate,'dau'=>$dau,'total_users'=>$total,'posts_today'=>$today,'avg_engagement'=>round($avgEng,1),'wau'=>$wau,'mau'=>$mau]];
}, 300);

hs_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
