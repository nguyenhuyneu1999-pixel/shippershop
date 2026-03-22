<?php
// ShipperShop API v2 — Admin Webhook Logs
// Track webhook delivery attempts, success/fail, response times
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

function wl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function wl_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') wl_fail('Admin only',403);

$key='webhook_logs';
$row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
$logs=$row?json_decode($row['value'],true):[];

// Add deploy webhook stats
$deployLogs=$d->fetchAll("SELECT action,details,created_at FROM audit_log WHERE action LIKE '%deploy%' ORDER BY created_at DESC LIMIT 20");

$successCount=0;$failCount=0;
foreach($logs as $l){if(($l['status']??'')==='success') $successCount++; else $failCount++;}
$successRate=($successCount+$failCount)>0?round($successCount/($successCount+$failCount)*100,1):100;

wl_ok('OK',['logs'=>array_slice($logs,-30),'deploy_logs'=>$deployLogs,'stats'=>['total'=>count($logs),'success'=>$successCount,'failed'=>$failCount,'success_rate'=>$successRate]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
