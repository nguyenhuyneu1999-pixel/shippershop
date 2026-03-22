<?php
// ShipperShop API v2 — Admin API Performance Dashboard
// Tinh nang: Theo doi hieu suat API endpoint
// Track API endpoint latency, error rates, request volume per endpoint
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

function apd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function apd_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') apd_fail('Admin only',403);

$data=cache_remember('api_performance_v2', function() use($d) {
    // Count API files
    $apiFiles=glob(__DIR__.'/*.php');
    $totalApis=count($apiFiles)-1; // minus index.php

    // Test key endpoints latency
    $endpoints=['/api/v2/status.php','/api/posts.php?limit=1','/api/v2/site-config.php'];
    $latencies=[];
    foreach($endpoints as $ep){
        $t=microtime(true);
        $ctx=stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]);
        @file_get_contents('https://shippershop.vn'.$ep,false,$ctx);
        $ms=round((microtime(true)-$t)*1000);
        $latencies[]=['endpoint'=>$ep,'latency_ms'=>$ms,'status'=>$ms<1000?'ok':'slow'];
    }

    $avgLatency=count($latencies)>0?round(array_sum(array_column($latencies,'latency_ms'))/count($latencies)):0;

    // Error count by day
    $errorsByDay=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as errors FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");

    // Rate limit hits
    $rateLimitHits=intval($d->fetchOne("SELECT COUNT(*) as c FROM rate_limits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);

    return ['total_apis'=>$totalApis,'latencies'=>$latencies,'avg_latency_ms'=>$avgLatency,'errors_by_day'=>$errorsByDay,'rate_limit_hits_24h'=>$rateLimitHits,'checked_at'=>date('c')];
}, 300);

apd_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
