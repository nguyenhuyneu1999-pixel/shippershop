<?php
// ShipperShop API v2 — Admin Notification Analytics
// Track notification delivery, open rates, engagement
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

function na_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function na_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') na_fail('Admin only',403);

$data=cache_remember('notif_analytics', function() use($d) {
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM notifications")['c']);
    $read=intval($d->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE is_read=1")['c']);
    $readRate=$total>0?round($read/$total*100,1):0;

    // By type
    $byType=$d->fetchAll("SELECT type,COUNT(*) as count,SUM(is_read) as read_count FROM notifications GROUP BY type ORDER BY count DESC LIMIT 10");
    foreach($byType as &$bt){$bt['read_rate']=$bt['count']>0?round(intval($bt['read_count'])/intval($bt['count'])*100,1):0;}unset($bt);

    // Daily trend
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as sent,SUM(is_read) as opened FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY day");

    // Push subscriptions
    $pushSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c']);

    return ['total'=>$total,'read'=>$read,'read_rate'=>$readRate,'by_type'=>$byType,'daily'=>$daily,'push_subscriptions'=>$pushSubs];
}, 600);

na_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
