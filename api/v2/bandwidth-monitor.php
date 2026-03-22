<?php
// ShipperShop API v2 — Admin Bandwidth Monitor
// Track storage usage, upload sizes, API response sizes
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

function bm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function bm_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') bm_fail('Admin only',403);

$data=cache_remember('bandwidth_monitor', function() use($d) {
    $uploadDirs=[
        ['name'=>'Posts','path'=>__DIR__.'/../../uploads/posts'],
        ['name'=>'Videos','path'=>__DIR__.'/../../uploads/videos'],
        ['name'=>'Avatars','path'=>__DIR__.'/../../uploads/avatars'],
        ['name'=>'Messages','path'=>__DIR__.'/../../uploads/messages'],
        ['name'=>'Traffic','path'=>__DIR__.'/../../uploads/traffic'],
    ];
    $storage=[];$totalBytes=0;
    foreach($uploadDirs as $dir){
        $size=0;$files=0;
        if(is_dir($dir['path'])){
            $iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir['path'],RecursiveDirectoryIterator::SKIP_DOTS));
            foreach($iter as $f){if($f->isFile()){$size+=$f->getSize();$files++;}}
        }
        $totalBytes+=$size;
        $storage[]=['name'=>$dir['name'],'files'=>$files,'size_mb'=>round($size/1048576,1)];
    }

    // DB size
    $dbSize=$d->fetchOne("SELECT SUM(data_length+index_length) as s FROM information_schema.tables WHERE table_schema=DATABASE()");
    $dbMb=round(intval($dbSize['s']??0)/1048576,1);

    // API file sizes
    $apiDir=__DIR__;
    $apiFiles=glob($apiDir.'/*.php');
    $apiSize=0;foreach($apiFiles as $f){$apiSize+=filesize($f);}

    return ['storage'=>$storage,'total_upload_mb'=>round($totalBytes/1048576,1),'db_size_mb'=>$dbMb,'api_code_kb'=>round($apiSize/1024,1),'api_count'=>count($apiFiles)];
}, 1800);

bm_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
