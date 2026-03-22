<?php
// ShipperShop API v2 — Admin Table Stats
// Detailed per-table row counts and sizes for database monitoring
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
function ats_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ats_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}
try {
$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ats_fail('Admin only',403);
$data=cache_remember('table_stats', function() use($d) {
    $tables=$d->fetchAll("SELECT table_name as name, table_rows as rows, ROUND(data_length/1024,1) as data_kb, ROUND(index_length/1024,1) as idx_kb, ROUND((data_length+index_length)/1024,1) as total_kb, auto_increment as next_id FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_rows DESC");
    $totalRows=array_sum(array_column($tables,'rows'));
    $totalKb=array_sum(array_map(function($t){return floatval($t['total_kb']);}, $tables));
    $largest=$tables[0]??null;$empty=array_filter($tables,function($t){return intval($t['rows'])===0;});
    return ['tables'=>$tables,'summary'=>['total_tables'=>count($tables),'total_rows'=>$totalRows,'total_kb'=>round($totalKb,1),'largest'=>$largest?$largest['name']:'','empty_tables'=>count($empty)]];
}, 1800);
ats_ok('OK',$data);
} catch (\Throwable $e) {echo json_encode(['success'=>false,'message'=>$e->getMessage()]);}
