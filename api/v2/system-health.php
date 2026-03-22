<?php
// ShipperShop API v2 — System Health Dashboard
// Comprehensive system health: DB size, API response times, error trends, disk, cache
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();

function sh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sh_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') sh_fail('Admin only',403);

$health=[];

// 1. Database
$tables=$pdo->query("SELECT TABLE_NAME,TABLE_ROWS,DATA_LENGTH,INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY DATA_LENGTH DESC")->fetchAll(PDO::FETCH_ASSOC);
$dbSize=0;$totalRows=0;$topTables=[];
foreach($tables as $t){
    $size=intval($t['DATA_LENGTH'])+intval($t['INDEX_LENGTH']);
    $dbSize+=$size;
    $totalRows+=intval($t['TABLE_ROWS']);
    if(count($topTables)<10) $topTables[]=['name'=>$t['TABLE_NAME'],'rows'=>intval($t['TABLE_ROWS']),'size_kb'=>round($size/1024,1)];
}
$health['database']=['tables'=>count($tables),'total_rows'=>$totalRows,'size_mb'=>round($dbSize/1048576,1),'top_tables'=>$topTables];

// 2. Disk
$diskTotal=@disk_total_space('/');
$diskFree=@disk_free_space('/');
$health['disk']=['total_gb'=>$diskTotal?round($diskTotal/1073741824,1):0,'free_gb'=>$diskFree?round($diskFree/1073741824,1):0,'used_pct'=>$diskTotal?round(($diskTotal-$diskFree)/$diskTotal*100,1):0];

// 3. Upload storage
$uploadSize=0;
$uploadDir=__DIR__.'/../../uploads/';
if(is_dir($uploadDir)){
    $iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
    foreach($iter as $f){if($f->isFile())$uploadSize+=$f->getSize();}
}
$health['uploads']=['size_mb'=>round($uploadSize/1048576,1),'path'=>'/uploads/'];

// 4. Error trends (last 7 days)
$errorTrend=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as errors FROM analytics_views WHERE page LIKE '_js_error%' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
$health['errors']=['trend'=>$errorTrend,'total_7d'=>array_sum(array_column($errorTrend,'errors'))];

// 5. Traffic (last 7 days)
$trafficTrend=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as views FROM analytics_views WHERE page NOT LIKE '\_%' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
$health['traffic']=['trend'=>$trafficTrend,'total_7d'=>array_sum(array_column($trafficTrend,'views'))];

// 6. User growth
$userGrowth=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as new_users FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day");
$health['user_growth']=['trend'=>$userGrowth,'total_30d'=>array_sum(array_column($userGrowth,'new_users'))];

// 7. PHP info
$health['php']=['version'=>phpversion(),'memory_limit'=>ini_get('memory_limit'),'max_execution_time'=>ini_get('max_execution_time'),'upload_max_filesize'=>ini_get('upload_max_filesize')];

// 8. API files count
$health['api']=['v2_files'=>count(glob(__DIR__.'/*.php')),'endpoints'=>count(glob(__DIR__.'/*.php'))-1];

// 9. Cron status
$health['cron']=['last_run'=>$d->fetchOne("SELECT MAX(created_at) as t FROM audit_log WHERE action LIKE 'cron%'")['t']??'Never'];

// 10. Active sessions (approx)
$health['active_users']=['online_now'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']),'active_24h'=>intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM analytics_views WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND user_id IS NOT NULL")['c'])];

sh_ok('OK',$health);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
