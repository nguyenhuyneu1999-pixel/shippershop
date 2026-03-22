<?php
// ShipperShop API v2 — Admin Platform Alerts
// Configure alerts for unusual platform activity
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

function pa2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pa2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') pa2_fail('Admin only',403);

if(!$action||$action==='check'){
    $alerts=[];
    // Check: no posts in 6 hours
    $recentPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)")['c']);
    if($recentPosts===0) $alerts[]=['type'=>'warning','title'=>'Khong co bai moi','desc'=>'Khong co bai viet nao trong 6 gio qua','severity'=>'medium'];

    // Check: spike in reports
    $recentReports=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);
    if($recentReports>=5) $alerts[]=['type'=>'danger','title'=>'Nhieu bao cao','desc'=>$recentReports.' bao cao trong 24h','severity'=>'high'];

    // Check: failed login spike
    $failLogins=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts WHERE success=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
    if($failLogins>=10) $alerts[]=['type'=>'danger','title'=>'Nhieu dang nhap that bai','desc'=>$failLogins.' lan/1h - co the bi tan cong','severity'=>'critical'];

    // Check: low engagement
    $todayPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
    $avgPosts=floatval($d->fetchOne("SELECT COUNT(*)/7 as a FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['a']??0);
    if($avgPosts>0&&$todayPosts<$avgPosts*0.3) $alerts[]=['type'=>'warning','title'=>'Tuong tac thap','desc'=>'Hom nay chi '.$todayPosts.' bai (TB '.(int)$avgPosts.')','severity'=>'medium'];

    // Check: disk/content queue
    $failedQueue=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='failed'")['c']);
    if($failedQueue>=3) $alerts[]=['type'=>'warning','title'=>'Hang doi bi loi','desc'=>$failedQueue.' muc that bai','severity'=>'medium'];

    if(!$alerts) $alerts[]=['type'=>'success','title'=>'Tat ca binh thuong','desc'=>'Khong phat hien van de','severity'=>'low'];

    pa2_ok('OK',['alerts'=>$alerts,'count'=>count($alerts),'checked_at'=>date('c')]);
}

pa2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
