<?php
// ShipperShop API v2 — Admin User Lifecycle
// Track user journey: signup → first post → active → churned
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

function ul_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ul_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ul_fail('Admin only',403);

$data=cache_remember('user_lifecycle', function() use($d) {
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $newThisWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $neverPosted=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts=0")['c']);
    $posted1=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts>=1")['c']);
    $active30=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $churned90=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts>=1 AND id NOT IN (SELECT DISTINCT user_id FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY))")['c']);

    // Avg time to first post
    $avgFirstPost=$d->fetchOne("SELECT AVG(TIMESTAMPDIFF(HOUR,u.created_at,(SELECT MIN(created_at) FROM posts WHERE user_id=u.id))) as avg_hours FROM users u WHERE u.`status`='active' AND u.total_posts>=1");
    $avgHours=round(floatval($avgFirstPost['avg_hours']??0),1);

    // Lifecycle stages
    $stages=[
        ['stage'=>'Dang ky','count'=>$total,'icon'=>'👤'],
        ['stage'=>'Dang bai dau tien','count'=>$posted1,'icon'=>'📝','pct'=>$total?round($posted1/$total*100,1):0],
        ['stage'=>'Active 30 ngay','count'=>$active30,'icon'=>'🟢','pct'=>$total?round($active30/$total*100,1):0],
        ['stage'=>'Chua bao gio dang','count'=>$neverPosted,'icon'=>'👻','pct'=>$total?round($neverPosted/$total*100,1):0],
        ['stage'=>'Churned 90d+','count'=>$churned90,'icon'=>'💤','pct'=>$total?round($churned90/$total*100,1):0],
    ];

    return ['stages'=>$stages,'new_this_week'=>$newThisWeek,'avg_hours_to_first_post'=>$avgHours,'total'=>$total];
}, 600);

ul_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
