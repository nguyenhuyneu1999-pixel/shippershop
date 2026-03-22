<?php
// ShipperShop API v2 — Admin Revenue Tracker
// Track platform revenue: subscriptions, deposits, transactions by period
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

function rv_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rv_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') rv_fail('Admin only',403);

$data=cache_remember('revenue_tracker', function() use($d) {
    $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed'")['s']);
    $monthRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
    $weekRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['s']);

    $activeSubscribers=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2")['c']);

    // Monthly trend
    $monthly=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, SUM(amount) as revenue, COUNT(*) as txns FROM wallet_transactions WHERE type='deposit' AND `status`='completed' GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month DESC LIMIT 6");

    // By plan
    $byPlan=$d->fetchAll("SELECT sp.name,COUNT(us.id) as subs FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.expires_at > NOW() GROUP BY us.plan_id ORDER BY subs DESC");

    return ['total'=>$totalRevenue,'month'=>$monthRevenue,'week'=>$weekRevenue,'subscribers'=>$activeSubscribers,'monthly_trend'=>array_reverse($monthly),'by_plan'=>$byPlan];
}, 600);

rv_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
