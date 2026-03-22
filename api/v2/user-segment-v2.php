<?php
// ShipperShop API v2 — Admin User Segmentation V2
// Auto-segment users: power users, casual, dormant, new, at-risk
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

function usv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function usv2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') usv2_fail('Admin only',403);

$data=cache_remember('user_segments_v2', function() use($d) {
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);

    // Power users: 10+ posts in 30 days
    $power=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY user_id HAVING COUNT(*)>=10")['c']??0);
    // Actually need different query
    $powerUsers=$d->fetchAll("SELECT user_id,COUNT(*) as cnt FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY user_id HAVING cnt>=10");
    $power=count($powerUsers);

    // Active: 1-9 posts in 30 days
    $activeUsers=$d->fetchAll("SELECT user_id FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY user_id HAVING COUNT(*) BETWEEN 1 AND 9");
    $active=count($activeUsers);

    // New: signed up in last 7 days
    $new=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);

    // Dormant: has posts but none in 30 days
    $dormant=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts>=1 AND id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))")['c']);

    // Never posted
    $never=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts=0")['c']);

    $segments=[
        ['name'=>'Power Users','count'=>$power,'icon'=>'⚡','color'=>'#7c3aed','desc'=>'10+ bai/30 ngay','pct'=>$total?round($power/$total*100,1):0],
        ['name'=>'Active','count'=>$active,'icon'=>'🟢','color'=>'#22c55e','desc'=>'1-9 bai/30 ngay','pct'=>$total?round($active/$total*100,1):0],
        ['name'=>'New','count'=>$new,'icon'=>'🆕','color'=>'#3b82f6','desc'=>'Dang ky 7 ngay','pct'=>$total?round($new/$total*100,1):0],
        ['name'=>'Dormant','count'=>$dormant,'icon'=>'💤','color'=>'#f59e0b','desc'=>'Khong dang 30d','pct'=>$total?round($dormant/$total*100,1):0],
        ['name'=>'Never Posted','count'=>$never,'icon'=>'👻','color'=>'#94a3b8','desc'=>'Chua bao gio dang','pct'=>$total?round($never/$total*100,1):0],
    ];

    return ['segments'=>$segments,'total'=>$total];
}, 600);

usv2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
