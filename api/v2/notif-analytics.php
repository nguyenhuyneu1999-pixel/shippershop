<?php
// ShipperShop API v2 — Admin Notification Analytics
// Track notification delivery, open rates, types distribution
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

function na3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function na3_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') na3_fail('Admin only',403);

$data=cache_remember('notif_analytics_v3', function() use($d) {
    $totalSent=intval($d->fetchOne("SELECT COUNT(*) as c FROM notifications")['c']);
    $totalRead=intval($d->fetchOne("SELECT COUNT(*) as c FROM notification_reads")['c']);
    $openRate=$totalSent>0?round($totalRead/$totalSent*100,1):0;

    // By type
    $byType=$d->fetchAll("SELECT type, COUNT(*) as c FROM notifications GROUP BY type ORDER BY c DESC LIMIT 10");

    // Recent 7 days daily
    $daily=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as sent FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");

    // Push subscriptions
    $pushSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c']);

    // Avg per user
    $uniqueUsers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM notifications")['c']);
    $avgPerUser=$uniqueUsers>0?round($totalSent/$uniqueUsers,1):0;

    return ['total_sent'=>$totalSent,'total_read'=>$totalRead,'open_rate'=>$openRate,'by_type'=>$byType,'daily'=>$daily,'push_subs'=>$pushSubs,'avg_per_user'=>$avgPerUser,'unique_users'=>$uniqueUsers];
}, 600);

na3_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
