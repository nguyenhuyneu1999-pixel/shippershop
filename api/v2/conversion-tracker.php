<?php
// ShipperShop API v2 — Admin Conversion Tracker
// Tinh nang: Theo doi ty le chuyen doi: visit→register→post→subscribe→revenue
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

function ct3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ct3_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ct3_fail('Admin only',403);

$days=min(intval($_GET['days']??30),90);

$data=cache_remember('conversion_tracker_'.$days, function() use($d,$days) {
    // Funnel stages
    $visitors=intval($d->fetchOne("SELECT COUNT(DISTINCT ip) as c FROM analytics_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $registered=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $firstPost=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) AND user_id IN (SELECT id FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY))")['c']);
    $subscribed=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE plan_id>=2 AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $revenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['s']);

    $base=max(1,$visitors);
    $funnel=[
        ['stage'=>'Visit','count'=>$visitors,'pct'=>100,'icon'=>'🌐','color'=>'#94a3b8'],
        ['stage'=>'Register','count'=>$registered,'pct'=>round($registered/$base*100,1),'icon'=>'📝','color'=>'#3b82f6'],
        ['stage'=>'First Post','count'=>$firstPost,'pct'=>round($firstPost/$base*100,1),'icon'=>'✍️','color'=>'#f59e0b'],
        ['stage'=>'Subscribe','count'=>$subscribed,'pct'=>round($subscribed/$base*100,1),'icon'=>'⭐','color'=>'#22c55e'],
    ];

    // Step rates
    for($i=1;$i<count($funnel);$i++){
        $prev=max(1,$funnel[$i-1]['count']);
        $funnel[$i]['step_rate']=round($funnel[$i]['count']/$prev*100,1);
    }
    $funnel[0]['step_rate']=100;

    // Daily registrations
    $dailyReg=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as regs FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");

    $overallRate=$visitors>0?round($subscribed/$visitors*100,2):0;
    $arpu=$subscribed>0?round($revenue/$subscribed):0;

    return ['funnel'=>$funnel,'overall_rate'=>$overallRate,'arpu'=>$arpu,'revenue'=>$revenue,'daily_regs'=>$dailyReg,'period_days'=>$days];
}, 1800);

ct3_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
