<?php
// ShipperShop API v2 — Admin User Segmentation V3
// Tinh nang: Phan nhom user nang cao theo hanh vi, gia tri, khu vuc
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

function usv3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function usv3_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') usv3_fail('Admin only',403);

$data=cache_remember('user_segment_v3', function() use($d) {
    $segments=[];

    // By activity level
    $superActive=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY user_id HAVING COUNT(*)>=5")['c']??0);
    $active=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $inactive=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c'])-$active;

    $segments[]=['name'=>'Sieu tich cuc','icon'=>'🔥','count'=>$superActive,'description'=>'5+ bai/tuan','color'=>'#ef4444'];
    $segments[]=['name'=>'Hoat dong','icon'=>'✅','count'=>$active,'description'=>'Dang bai trong 30 ngay','color'=>'#22c55e'];
    $segments[]=['name'=>'Im lang','icon'=>'😴','count'=>max(0,$inactive),'description'=>'Khong dang bai 30 ngay+','color'=>'#94a3b8'];

    // By company
    $byCompany=$d->fetchAll("SELECT shipping_company as company, COUNT(*) as c FROM users WHERE `status`='active' AND shipping_company IS NOT NULL AND shipping_company!='' GROUP BY shipping_company ORDER BY c DESC LIMIT 8");

    // By subscription
    $freeSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND id NOT IN (SELECT user_id FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2)")['c']);
    $paidSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2")['c']);

    // By province
    $byProvince=$d->fetchAll("SELECT province, COUNT(*) as c FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY c DESC LIMIT 5");

    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);

    return ['segments'=>$segments,'by_company'=>$byCompany,'subscription'=>['free'=>$freeSubs,'paid'=>$paidSubs],'by_province'=>$byProvince,'total_users'=>$total];
}, 1800);

usv3_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
