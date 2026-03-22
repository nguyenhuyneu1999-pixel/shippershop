<?php
// ShipperShop API v2 — Admin User Funnel
// Visit → Register → First Post → Active → Subscriber conversion funnel
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

function uf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function uf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') uf_fail('Admin only',403);

$data=cache_remember('user_funnel', function() use($d) {
    $totalVisitors=intval($d->fetchOne("SELECT COUNT(DISTINCT ip) as c FROM analytics_views")['c']);
    $registered=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $firstPost=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts>=1")['c']);
    $active30=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $subscribers=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2")['c']);
    $followers5=intval($d->fetchOne("SELECT COUNT(DISTINCT following_id) as c FROM follows GROUP BY following_id HAVING COUNT(*)>=5")['c']);

    $stages=[
        ['stage'=>'Truy cap','count'=>$totalVisitors,'icon'=>'👁️'],
        ['stage'=>'Dang ky','count'=>$registered,'icon'=>'📝','rate'=>$totalVisitors>0?round($registered/$totalVisitors*100,1):0],
        ['stage'=>'Bai dau tien','count'=>$firstPost,'icon'=>'✍️','rate'=>$registered>0?round($firstPost/$registered*100,1):0],
        ['stage'=>'Active 30d','count'=>$active30,'icon'=>'🟢','rate'=>$firstPost>0?round($active30/$firstPost*100,1):0],
        ['stage'=>'Subscriber','count'=>$subscribers,'icon'=>'⭐','rate'=>$active30>0?round($subscribers/$active30*100,1):0],
    ];

    $overallRate=$totalVisitors>0?round($subscribers/$totalVisitors*100,2):0;

    return ['stages'=>$stages,'overall_conversion'=>$overallRate];
}, 600);

uf_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
