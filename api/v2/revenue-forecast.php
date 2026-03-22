<?php
// ShipperShop API v2 — Admin Revenue Forecast
// Predict future revenue based on historical trends
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

function rf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') rf_fail('Admin only',403);

$data=cache_remember('revenue_forecast', function() use($d) {
    // Historical monthly revenue
    $monthly=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month, SUM(amount) as revenue, COUNT(*) as txns FROM wallet_transactions WHERE type='deposit' AND amount>0 GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month DESC LIMIT 6");

    $currentMonth=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
    $dayOfMonth=intval(date('j'));
    $daysInMonth=intval(date('t'));
    $projectedMonth=$dayOfMonth>0?round($currentMonth/$dayOfMonth*$daysInMonth):0;

    // Growth rate
    $lastMonth=0;$prevMonth=0;
    if(count($monthly)>=2){$lastMonth=intval($monthly[0]['revenue']??0);$prevMonth=intval($monthly[1]['revenue']??0);}
    $growthRate=$prevMonth>0?round(($lastMonth-$prevMonth)/$prevMonth*100,1):0;

    // Forecast next 3 months
    $forecast=[];
    $base=$projectedMonth>0?$projectedMonth:($lastMonth>0?$lastMonth:200000);
    $rate=1+$growthRate/100;
    for($i=1;$i<=3;$i++){
        $forecast[]=['month'=>date('Y-m',strtotime("+$i month")),'projected'=>round($base*pow($rate,$i)),'confidence'=>max(30,100-$i*20)];
    }

    // Subscription revenue projection
    $activeSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2")['c']);
    $avgSubRevenue=intval($d->fetchOne("SELECT AVG(sp.price) as a FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.expires_at > NOW() AND us.plan_id>=2")['a']??0);
    $monthlySubRevenue=$activeSubs*$avgSubRevenue;

    return ['current_month'=>$currentMonth,'projected_month'=>$projectedMonth,'growth_rate'=>$growthRate,'historical'=>$monthly,'forecast'=>$forecast,'subscriptions'=>['active'=>$activeSubs,'avg_price'=>$avgSubRevenue,'monthly_revenue'=>$monthlySubRevenue]];
}, 600);

rf_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
