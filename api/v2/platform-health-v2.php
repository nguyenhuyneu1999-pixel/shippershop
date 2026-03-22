<?php
// ShipperShop API v2 — Admin Platform Health V2
// Comprehensive health: API latency, error rates, DB performance, uptime
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

function phv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function phv2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') phv2_fail('Admin only',403);

$data=cache_remember('platform_health_v2', function() use($d) {
    $checks=[];$t0=microtime(true);

    // DB read latency
    $t1=microtime(true);
    $d->fetchOne("SELECT 1");
    $dbReadMs=round((microtime(true)-$t1)*1000,1);
    $checks[]=['name'=>'DB Read','value'=>$dbReadMs.'ms','status'=>$dbReadMs<50?'ok':($dbReadMs<200?'warning':'critical')];

    // DB write latency
    $t2=microtime(true);
    $d->query("UPDATE settings SET value=value WHERE `key`='_health_check' LIMIT 1");
    $dbWriteMs=round((microtime(true)-$t2)*1000,1);
    $checks[]=['name'=>'DB Write','value'=>$dbWriteMs.'ms','status'=>$dbWriteMs<100?'ok':($dbWriteMs<500?'warning':'critical')];

    // Disk
    $diskFree=disk_free_space('/');$diskTotal=disk_total_space('/');
    $diskPct=$diskTotal>0?round((1-$diskFree/$diskTotal)*100,1):0;
    $checks[]=['name'=>'Disk','value'=>$diskPct.'%','status'=>$diskPct<80?'ok':($diskPct<90?'warning':'critical')];

    // Error rate (24h)
    $errors24=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);
    $checks[]=['name'=>'Errors 24h','value'=>$errors24,'status'=>$errors24<10?'ok':($errors24<50?'warning':'critical')];

    // DB size
    $dbSize=floatval($d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1048576,1) as mb FROM information_schema.tables WHERE table_schema=DATABASE()")['mb']??0);
    $checks[]=['name'=>'DB Size','value'=>$dbSize.'MB','status'=>$dbSize<200?'ok':($dbSize<500?'warning':'critical')];

    // PHP memory
    $memUsed=round(memory_get_usage(true)/1048576,1);
    $checks[]=['name'=>'PHP Memory','value'=>$memUsed.'MB','status'=>$memUsed<64?'ok':($memUsed<128?'warning':'critical')];

    // Active connections
    $threads=intval($d->fetchOne("SHOW STATUS LIKE 'Threads_connected'")['Value']??0);
    $checks[]=['name'=>'DB Connections','value'=>$threads,'status'=>$threads<50?'ok':($threads<100?'warning':'critical')];

    $totalMs=round((microtime(true)-$t0)*1000,1);
    $okCount=count(array_filter($checks,function($c){return $c['status']==='ok';}));
    $overallStatus=$okCount===count($checks)?'healthy':($okCount>=count($checks)-1?'degraded':'unhealthy');

    return ['checks'=>$checks,'overall'=>$overallStatus,'ok_count'=>$okCount,'total_checks'=>count($checks),'check_time_ms'=>$totalMs];
}, 60);

phv2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
