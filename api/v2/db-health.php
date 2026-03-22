<?php
// ShipperShop API v2 — Admin Database Health
// Monitor table sizes, index usage, slow queries, fragmentation
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

function dbh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function dbh_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') dbh_fail('Admin only',403);

$data=cache_remember('db_health', function() use($d) {
    // Table sizes
    $tables=$d->fetchAll("SELECT table_name as name, table_rows as rows, ROUND(data_length/1024,1) as data_kb, ROUND(index_length/1024,1) as index_kb, ROUND((data_length+index_length)/1024,1) as total_kb FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY data_length+index_length DESC LIMIT 20");

    $totalSize=$d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1048576,1) as mb FROM information_schema.tables WHERE table_schema=DATABASE()");
    $totalTables=intval($d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()")['c']);
    $totalRows=$d->fetchOne("SELECT SUM(table_rows) as r FROM information_schema.tables WHERE table_schema=DATABASE()");

    // Index count
    $indexCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM information_schema.statistics WHERE table_schema=DATABASE()")['c']);

    // Fragmentation
    $fragmented=$d->fetchAll("SELECT table_name as name, ROUND(data_free/1024,1) as free_kb FROM information_schema.tables WHERE table_schema=DATABASE() AND data_free > 10240 ORDER BY data_free DESC LIMIT 5");

    $health=100;
    if(floatval($totalSize['mb']??0)>100) $health-=10;
    if(count($fragmented)>3) $health-=10;
    $grade=$health>=80?'A':($health>=60?'B':'C');

    return ['tables'=>$tables,'total_mb'=>floatval($totalSize['mb']??0),'total_tables'=>$totalTables,'total_rows'=>intval($totalRows['r']??0),'index_count'=>$indexCount,'fragmented'=>$fragmented,'health'=>$health,'grade'=>$grade];
}, 1800);

dbh_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
