<?php
// ShipperShop API v2 — Admin Cron Monitor
// Monitor cron job health: last run, duration, success/fail, schedule
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

function cm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cm2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') cm2_fail('Admin only',403);

$data=cache_remember('cron_monitor', function() use($d) {
    $jobs=[
        ['name'=>'auto-content','schedule'=>'Every 6h','description'=>'Auto-generate posts'],
        ['name'=>'auto-publish','schedule'=>'Every 1h','description'=>'Publish scheduled content'],
        ['name'=>'streak-update','schedule'=>'Daily 00:00','description'=>'Update user streaks'],
        ['name'=>'subscription-check','schedule'=>'Daily 06:00','description'=>'Check expired subscriptions'],
        ['name'=>'cleanup','schedule'=>'Weekly','description'=>'Clean temp files + old sessions'],
        ['name'=>'analytics','schedule'=>'Every 4h','description'=>'Aggregate analytics data'],
        ['name'=>'backup-check','schedule'=>'Daily 02:00','description'=>'Verify backup status'],
        ['name'=>'notification-digest','schedule'=>'Daily 08:00','description'=>'Send notification digests'],
    ];

    // Get last run info from cron_logs
    foreach($jobs as &$j){
        $log=$d->fetchOne("SELECT status,duration_ms,created_at FROM cron_logs WHERE job_name=? ORDER BY created_at DESC LIMIT 1",[$j['name']]);
        $j['last_run']=$log?$log['created_at']:null;
        $j['last_status']=$log?$log['status']:'never';
        $j['last_duration_ms']=$log?intval($log['duration_ms']):0;
        $j['healthy']=$log&&$log['status']==='success';
    }unset($j);

    $healthy=count(array_filter($jobs,function($j){return $j['healthy'];}));
    $overallHealth=count($jobs)>0?round($healthy/count($jobs)*100):0;

    return ['jobs'=>$jobs,'healthy'=>$healthy,'total'=>count($jobs),'health_pct'=>$overallHealth];
}, 300);

cm2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
