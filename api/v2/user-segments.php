<?php
// ShipperShop API v2 — Admin User Segments
// Segment users by activity, company, location, subscription
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

function us_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function us_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') us_fail('Admin only',403);

if(!$action||$action==='overview'){
    $data=cache_remember('user_segments', function() use($d) {
        // By activity level
        $active30d=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
        $active7d=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
        $dormant=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY))")['c']);
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);

        // By company
        $byCompany=$d->fetchAll("SELECT shipping_company,COUNT(*) as users FROM users WHERE `status`='active' AND shipping_company IS NOT NULL AND shipping_company!='' GROUP BY shipping_company ORDER BY users DESC LIMIT 15");

        // By subscription
        $bySub=$d->fetchAll("SELECT sp.name,COUNT(us.id) as subscribers FROM subscription_plans sp LEFT JOIN user_subscriptions us ON sp.id=us.plan_id AND us.expires_at > NOW() GROUP BY sp.id ORDER BY sp.price");

        // By join date (cohorts)
        $cohorts=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m') as month,COUNT(*) as users FROM users WHERE `status`='active' GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month DESC LIMIT 12");

        // Power users (top 10 by posts)
        $power=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.total_posts,u.total_success FROM users u WHERE u.`status`='active' ORDER BY u.total_posts DESC LIMIT 10");

        return [
            'activity'=>['total'=>$total,'active_30d'=>$active30d,'active_7d'=>$active7d,'dormant'=>$dormant,'inactive'=>$total-$active30d-$dormant],
            'by_company'=>$byCompany,
            'by_subscription'=>$bySub,
            'cohorts'=>$cohorts,
            'power_users'=>$power,
        ];
    }, 600);
    us_ok('OK',$data);
}

us_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
