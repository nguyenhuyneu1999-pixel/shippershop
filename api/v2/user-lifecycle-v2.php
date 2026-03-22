<?php
// ShipperShop API v2 — Admin User Lifecycle V2
// Track user journey stages: new→active→power→dormant→churned with transitions
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

function ulv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ulv2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ulv2_fail('Admin only',403);

$data=cache_remember('user_lifecycle_v2', function() use($d) {
    $stages=['new'=>0,'active'=>0,'power'=>0,'dormant'=>0,'churned'=>0];

    // New: registered within 7 days
    $stages['new']=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);

    // Power: 10+ posts in last 30 days
    $stages['power']=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY user_id HAVING COUNT(*)>=10")['c']??0);

    // Active: posted in last 30 days but <10 posts
    $totalActive=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $stages['active']=$totalActive-$stages['power'];

    // Churned: no posts in 60+ days but had posts before
    $stages['churned']=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND user_id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)) AND created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)")['c']);

    // Dormant: no posts in 30-60 days
    $stages['dormant']=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND user_id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);

    $total=array_sum($stages);

    // Stage details
    $stageInfo=[
        ['id'=>'new','name'=>'Moi','icon'=>'🆕','count'=>$stages['new'],'pct'=>$total>0?round($stages['new']/$total*100,1):0,'color'=>'#3b82f6'],
        ['id'=>'active','name'=>'Hoat dong','icon'=>'✅','count'=>$stages['active'],'pct'=>$total>0?round($stages['active']/$total*100,1):0,'color'=>'#22c55e'],
        ['id'=>'power','name'=>'Nang dong','icon'=>'⭐','count'=>$stages['power'],'pct'=>$total>0?round($stages['power']/$total*100,1):0,'color'=>'#f59e0b'],
        ['id'=>'dormant','name'=>'Nghi dong','icon'=>'😴','count'=>$stages['dormant'],'pct'=>$total>0?round($stages['dormant']/$total*100,1):0,'color'=>'#94a3b8'],
        ['id'=>'churned','name'=>'Roi di','icon'=>'💨','count'=>$stages['churned'],'pct'=>$total>0?round($stages['churned']/$total*100,1):0,'color'=>'#ef4444'],
    ];

    return ['stages'=>$stageInfo,'total_tracked'=>$total,'health_ratio'=>$total>0?round(($stages['active']+$stages['power'])/$total*100,1):0];
}, 1800);

ulv2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
