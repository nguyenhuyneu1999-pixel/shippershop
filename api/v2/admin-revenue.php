<?php
// ShipperShop API v2 — Admin Revenue Dashboard
// Revenue analytics: deposits, subscriptions, trends
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

$d=db();$action=$_GET['action']??'';

function ar_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ar_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ar_fail('Admin only',403);

if(!$action||$action==='overview'){
    $data=cache_remember('admin_revenue', function() use($d) {
        $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']);
        $monthRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
        $weekRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['s']);
        $activeSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW()")['c']);
        $pendingDeposits=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);
        $pendingAmount=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM payos_payments WHERE `status` IN ('pending','manual')")['s']);

        // Monthly trend (last 6 months)
        $monthly=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month,SUM(amount) as revenue,COUNT(*) as txns FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month");

        // Subscription breakdown
        $subs=$d->fetchAll("SELECT sp.name,sp.price,COUNT(us.id) as subscribers FROM subscription_plans sp LEFT JOIN user_subscriptions us ON sp.id=us.plan_id AND us.expires_at > NOW() GROUP BY sp.id ORDER BY sp.price DESC");

        return ['total_revenue'=>$totalRevenue,'month_revenue'=>$monthRevenue,'week_revenue'=>$weekRevenue,'active_subscriptions'=>$activeSubs,'pending_deposits'=>$pendingDeposits,'pending_amount'=>$pendingAmount,'monthly_trend'=>$monthly,'subscription_breakdown'=>$subs];
    }, 300);
    ar_ok('OK',$data);
}

// Recent transactions
if($action==='recent'){
    $limit=min(intval($_GET['limit']??20),100);
    $txns=$d->fetchAll("SELECT wt.*,u.fullname,u.avatar FROM wallet_transactions wt JOIN users u ON wt.user_id=u.id ORDER BY wt.created_at DESC LIMIT $limit");
    ar_ok('OK',$txns);
}

ar_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
