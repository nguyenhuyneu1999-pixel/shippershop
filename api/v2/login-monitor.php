<?php
// ShipperShop API v2 — Admin Login Monitor
// Track login attempts, success/fail rates, suspicious activity
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

function lm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function lm2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') lm2_fail('Admin only',403);

$data=cache_remember('login_monitor', function() use($d) {
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts")['c']);
    $today=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts WHERE created_at >= CURDATE()")['c']);
    $failed=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts WHERE success=0")['c']);
    $failRate=$total>0?round($failed/$total*100,1):0;

    $recent=$d->fetchAll("SELECT email,ip,success,created_at FROM login_attempts ORDER BY created_at DESC LIMIT 20");

    // Suspicious: same IP, many failures
    $suspiciousIPs=$d->fetchAll("SELECT ip, COUNT(*) as attempts, SUM(CASE WHEN success=0 THEN 1 ELSE 0 END) as fails FROM login_attempts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY ip HAVING fails >= 3 ORDER BY fails DESC LIMIT 10");

    return ['total'=>$total,'today'=>$today,'failed'=>$failed,'fail_rate'=>$failRate,'recent'=>$recent,'suspicious_ips'=>$suspiciousIPs];
}, 300);

lm2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
