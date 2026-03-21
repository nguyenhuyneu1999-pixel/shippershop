<?php
// ShipperShop API v2 — Admin Revenue Dashboard
// Revenue analytics: subscriptions, deposits, trends, projections
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

$d=db();$action=$_GET['action']??'';

function ar_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ar_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ar_fail('Admin only',403);

// Overview
if(!$action||$action==='overview'){
    $data=cache_remember('admin_revenue', function() use($d) {
        $totalRev=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']);
        $monthRev=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
        $weekRev=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['s']);
        $todayRev=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= CURDATE()")['s']);

        // Active subscriptions
        $activeSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW()")['c']);
        $subBreakdown=$d->fetchAll("SELECT sp.name,sp.price,COUNT(us.id) as count FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.expires_at > NOW() GROUP BY us.plan_id ORDER BY count DESC");

        // Monthly trend (6 months)
        $monthly=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month,SUM(amount) as revenue,COUNT(*) as transactions FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month");

        // Top depositors
        $topUsers=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,SUM(wt.amount) as total FROM wallet_transactions wt JOIN users u ON wt.user_id=u.id WHERE wt.type='deposit' AND wt.amount>0 GROUP BY wt.user_id ORDER BY total DESC LIMIT 10");

        // Pending deposits
        $pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);
        $pendingAmount=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM payos_payments WHERE `status` IN ('pending','manual')")['s']);

        return [
            'revenue'=>['total'=>$totalRev,'month'=>$monthRev,'week'=>$weekRev,'today'=>$todayRev],
            'subscriptions'=>['active'=>$activeSubs,'breakdown'=>$subBreakdown],
            'monthly_trend'=>$monthly,
            'top_users'=>$topUsers,
            'pending'=>['count'=>$pending,'amount'=>$pendingAmount],
        ];
    }, 300);
    ar_ok('OK',$data);
}

// Daily breakdown for current month
if($action==='daily'){
    $days=$d->fetchAll("SELECT DATE(created_at) as day,SUM(amount) as revenue,COUNT(*) as txns FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01') GROUP BY DATE(created_at) ORDER BY day");
    ar_ok('OK',$days);
}

ar_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
