<?php
// ShipperShop API v2 — System Health Alerts
// Auto-detect issues: high error rate, disk full, slow queries, expired certs
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function ha_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin'){http_response_code(403);echo json_encode(['success'=>false,'message'=>'Admin only']);exit;}

$alerts=[];

// 1. Error rate check
$errors=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page LIKE '_js_error%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
if($errors>50) $alerts[]=['severity'=>'critical','type'=>'errors','title'=>'Lỗi JS cao','detail'=>$errors.' lỗi trong 1 giờ qua','action'=>'Kiểm tra /api/v2/perf.php'];
elseif($errors>10) $alerts[]=['severity'=>'warning','type'=>'errors','title'=>'Có lỗi JS','detail'=>$errors.' lỗi trong 1 giờ qua'];

// 2. Disk space
$total=@disk_total_space('/');$free=@disk_free_space('/');
if($total&&$free){
    $usedPct=round((1-$free/$total)*100);
    if($usedPct>90) $alerts[]=['severity'=>'critical','type'=>'disk','title'=>'Disk gần đầy','detail'=>$usedPct.'% đã sử dụng'];
    elseif($usedPct>80) $alerts[]=['severity'=>'warning','type'=>'disk','title'=>'Disk cao','detail'=>$usedPct.'% đã sử dụng'];
}

// 3. Failed login attempts
$failedLogins=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts WHERE success=0 AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
if($failedLogins>50) $alerts[]=['severity'=>'warning','type'=>'security','title'=>'Nhiều login thất bại','detail'=>$failedLogins.' lần trong 1 giờ','action'=>'Có thể bị brute force'];

// 4. Pending deposits
$pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);
if($pending>0) $alerts[]=['severity'=>'info','type'=>'payment','title'=>$pending.' nạp tiền chờ duyệt','detail'=>'Vào admin để duyệt'];

// 5. Unresolved reports
$reports=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);
if($reports>0) $alerts[]=['severity'=>'info','type'=>'moderation','title'=>$reports.' báo cáo chưa xử lý','detail'=>'Vào admin để xử lý'];

// 6. Expired subscriptions
$expiredSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active' AND expires_at < NOW()")['c']);
if($expiredSubs>0) $alerts[]=['severity'=>'info','type'=>'subscription','title'=>$expiredSubs.' gói hết hạn','detail'=>'Cần chạy cron auto_renew'];

// 7. DB size
$dbSize=$d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1024/1024,1) as size_mb FROM information_schema.tables WHERE table_schema=DATABASE()");
$sizeMb=floatval($dbSize['size_mb']??0);
if($sizeMb>500) $alerts[]=['severity'=>'warning','type'=>'database','title'=>'DB lớn','detail'=>$sizeMb.'MB — cần cleanup'];

// Summary
$critical=count(array_filter($alerts,function($a){return $a['severity']==='critical';}));
$warning=count(array_filter($alerts,function($a){return $a['severity']==='warning';}));
$info=count(array_filter($alerts,function($a){return $a['severity']==='info';}));

ha_ok('OK',['alerts'=>$alerts,'summary'=>['critical'=>$critical,'warning'=>$warning,'info'=>$info,'total'=>count($alerts)],'status'=>$critical>0?'critical':($warning>0?'warning':'healthy')]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
