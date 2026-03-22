<?php
// ShipperShop API v2 — Admin Stats Cache
// Pre-computed dashboard stats, cache management
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

$d=db();$action=$_GET['action']??'';

function asc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function asc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') asc_fail('Admin only',403);

// Refresh all cached stats
if(!$action||$action==='refresh'){
    // Clear cache entries
    $d->query("DELETE FROM settings WHERE `key` LIKE 'cache_%'");
    $cleared=intval($d->fetchOne("SELECT ROW_COUNT() as c")['c']??0);
    asc_ok('Da lam moi cache',['cleared'=>$cleared]);
}

// Cache stats
if($action==='info'){
    $cacheEntries=$d->fetchAll("SELECT `key`,LENGTH(value) as size FROM settings WHERE `key` LIKE 'cache_%' ORDER BY size DESC LIMIT 20");
    $totalSize=intval($d->fetchOne("SELECT COALESCE(SUM(LENGTH(value)),0) as s FROM settings WHERE `key` LIKE 'cache_%'")['s']);
    $totalCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM settings WHERE `key` LIKE 'cache_%'")['c']);
    asc_ok('OK',['entries'=>$cacheEntries,'total_count'=>$totalCount,'total_size_kb'=>round($totalSize/1024,2)]);
}

asc_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
