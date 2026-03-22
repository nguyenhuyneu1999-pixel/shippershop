<?php
// ShipperShop API v2 — Admin API Usage Monitor
// Track API call frequency, response times, error rates
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

function au2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function au2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') au2_fail('Admin only',403);

$data=cache_remember('api_usage_monitor', function() use($d) {
    // Count API files
    $apiDir=__DIR__;
    $apiFiles=glob($apiDir.'/*.php');
    $totalApis=count($apiFiles)-1; // minus index.php

    // Rate limit data (proxy for API usage)
    $totalCalls=intval($d->fetchOne("SELECT COUNT(*) as c FROM rate_limits")['c']);
    $recentCalls=intval($d->fetchOne("SELECT COUNT(*) as c FROM rate_limits WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);

    // Error rate from audit log
    $totalAudit=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);
    $errorAudit=intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c']);
    $errorRate=$totalAudit>0?round($errorAudit/$totalAudit*100,1):0;

    // Top endpoints by views
    $topPages=$d->fetchAll("SELECT page,COUNT(*) as views FROM analytics_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY page ORDER BY views DESC LIMIT 10");

    // Hourly distribution
    $hourly=$d->fetchAll("SELECT HOUR(created_at) as h, COUNT(*) as c FROM analytics_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY HOUR(created_at) ORDER BY h");

    return ['total_apis'=>$totalApis,'total_rate_entries'=>$totalCalls,'calls_last_hour'=>$recentCalls,'error_rate_24h'=>$errorRate,'top_pages'=>$topPages,'hourly'=>$hourly,'checked_at'=>date('c')];
}, 300);

au2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
