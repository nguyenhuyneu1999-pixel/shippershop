<?php
// ShipperShop API v2 — Admin Platform Scorecard
// Overall platform health score across multiple dimensions
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

function psc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function psc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') psc_fail('Admin only',403);

$data=cache_remember('platform_scorecard', function() use($d) {
    $dimensions=[];

    // 1. Growth (user growth rate)
    $usersThisMonth=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['c']);
    $usersLastMonth=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at BETWEEN DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH),'%Y-%m-01') AND DATE_FORMAT(NOW(),'%Y-%m-01')")['c']);
    $growthScore=min(100,$usersLastMonth>0?round(($usersThisMonth/$usersLastMonth)*50):($usersThisMonth>0?60:20));
    $dimensions[]=['name'=>'Tang truong','score'=>$growthScore,'icon'=>'📈','detail'=>'+'.$usersThisMonth.' users thang nay'];

    // 2. Engagement (posts per active user)
    $mau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $monthPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $postsPerUser=$mau>0?round($monthPosts/$mau,1):0;
    $engScore=min(100,round($postsPerUser*10));
    $dimensions[]=['name'=>'Tuong tac','score'=>$engScore,'icon'=>'💬','detail'=>$postsPerUser.' bai/user/thang'];

    // 3. Retention (DAU/MAU stickiness)
    $dau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= CURDATE()")['c']);
    $stickiness=$mau>0?round($dau/$mau*100):0;
    $retScore=min(100,round($stickiness*2));
    $dimensions[]=['name'=>'Giu chan','score'=>$retScore,'icon'=>'🔄','detail'=>$stickiness.'% stickiness'];

    // 4. Content (avg engagement per post)
    $avgEng=floatval($d->fetchOne("SELECT AVG(likes_count+comments_count) as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['a']??0);
    $contentScore=min(100,round($avgEng*15));
    $dimensions[]=['name'=>'Noi dung','score'=>$contentScore,'icon'=>'📝','detail'=>round($avgEng,1).' eng TB/bai'];

    // 5. Revenue
    $monthRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
    $revScore=min(100,$monthRevenue>0?round(min($monthRevenue/1000000*100,100)):10);
    $dimensions[]=['name'=>'Doanh thu','score'=>$revScore,'icon'=>'💰','detail'=>number_format($monthRevenue).'d'];

    $overallScore=count($dimensions)>0?round(array_sum(array_column($dimensions,'score'))/count($dimensions)):0;
    $grade=$overallScore>=80?'A':($overallScore>=60?'B':($overallScore>=40?'C':'D'));

    return ['dimensions'=>$dimensions,'overall'=>$overallScore,'grade'=>$grade,'generated_at'=>date('c')];
}, 600);

psc_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
