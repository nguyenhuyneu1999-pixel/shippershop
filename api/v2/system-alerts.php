<?php
// ShipperShop API v2 — Admin System Alerts
// Real-time system health alerts: disk, DB, API errors, performance
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

function sa2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sa2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') sa2_fail('Admin only',403);

$data=cache_remember('system_alerts_v2', function() use($d) {
    $alerts=[];

    // Disk usage
    $diskFree=disk_free_space('/');$diskTotal=disk_total_space('/');
    $diskUsedPct=$diskTotal>0?round((1-$diskFree/$diskTotal)*100,1):0;
    if($diskUsedPct>90) $alerts[]=['type'=>'critical','category'=>'disk','message'=>'Disk usage '.$diskUsedPct.'%','icon'=>'💾'];
    elseif($diskUsedPct>80) $alerts[]=['type'=>'warning','category'=>'disk','message'=>'Disk usage '.$diskUsedPct.'%','icon'=>'💾'];

    // DB size
    $dbSize=$d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1048576,1) as mb FROM information_schema.tables WHERE table_schema=DATABASE()");
    $dbMb=floatval($dbSize['mb']??0);
    if($dbMb>200) $alerts[]=['type'=>'warning','category'=>'db','message'=>'DB size '.$dbMb.'MB','icon'=>'🗄️'];

    // Error rate
    $errors24h=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);
    if($errors24h>50) $alerts[]=['type'=>'critical','category'=>'errors','message'=>$errors24h.' errors in 24h','icon'=>'🐛'];
    elseif($errors24h>10) $alerts[]=['type'=>'warning','category'=>'errors','message'=>$errors24h.' errors in 24h','icon'=>'🐛'];

    // Failed logins
    $failedLogins=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts WHERE success=0 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
    if($failedLogins>10) $alerts[]=['type'=>'warning','category'=>'security','message'=>$failedLogins.' failed logins/hour','icon'=>'🔐'];

    // No alerts = healthy
    if(empty($alerts)) $alerts[]=['type'=>'info','category'=>'status','message'=>'He thong binh thuong','icon'=>'✅'];

    $severity='healthy';
    foreach($alerts as $a){if($a['type']==='critical'){$severity='critical';break;}if($a['type']==='warning') $severity='warning';}

    return ['alerts'=>$alerts,'severity'=>$severity,'disk_pct'=>$diskUsedPct,'db_mb'=>$dbMb,'errors_24h'=>$errors24h,'checked_at'=>date('c')];
}, 120);

sa2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
