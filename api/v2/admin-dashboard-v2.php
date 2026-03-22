<?php
// ShipperShop API v2 — Admin Dashboard v2
// Comprehensive admin dashboard with real-time metrics
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

function ad2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ad2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ad2_fail('Admin only',403);

$data=cache_remember('admin_dash_v2', function() use($d) {
    // Key metrics
    $total_users=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $today_users=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()")['c']);
    $week_users=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $online=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']);
    $total_posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $today_posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
    $total_comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']);
    $total_deliveries=intval($d->fetchOne("SELECT COALESCE(SUM(total_success),0) as s FROM users")['s']);
    $revenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']);
    $month_revenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
    $active_subs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW()")['c']);
    $pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);
    $reports=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);
    $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c']);
    $conversations=intval($d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c']);

    // 7-day growth
    $growth=[];
    for($i=6;$i>=0;$i--){
        $date=date('Y-m-d',strtotime("-$i days"));
        $u=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE DATE(created_at)=?",[$date])['c']);
        $p=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND DATE(created_at)=?",[$date])['c']);
        $growth[]=['date'=>$date,'users'=>$u,'posts'=>$p];
    }

    // Top 5 active today
    $topToday=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,COUNT(p.id) as posts FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= CURDATE() GROUP BY p.user_id ORDER BY posts DESC LIMIT 5");

    return [
        'metrics'=>['users'=>$total_users,'users_today'=>$today_users,'users_week'=>$week_users,'online'=>$online,'posts'=>$total_posts,'posts_today'=>$today_posts,'comments'=>$total_comments,'deliveries'=>$total_deliveries,'revenue'=>$revenue,'month_revenue'=>$month_revenue,'subscriptions'=>$active_subs,'pending_deposits'=>$pending,'pending_reports'=>$reports,'groups'=>$groups,'conversations'=>$conversations],
        'growth_7d'=>$growth,
        'top_today'=>$topToday,
        'api_version'=>'5.0.0',
        'generated_at'=>date('c'),
    ];
}, 120);

ad2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
