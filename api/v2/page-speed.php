<?php
// ShipperShop API v2 — Admin Page Speed Monitor
// Track page load times, asset sizes, performance scores
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

function ps_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ps_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ps_fail('Admin only',403);

$data=cache_remember('page_speed', function() {
    $pages=[
        ['url'=>'/','name'=>'Trang chu'],
        ['url'=>'/login.html','name'=>'Dang nhap'],
        ['url'=>'/groups.html','name'=>'Hoi nhom'],
        ['url'=>'/marketplace.html','name'=>'Cho'],
        ['url'=>'/messages.html','name'=>'Tin nhan'],
        ['url'=>'/traffic.html','name'=>'Giao thong'],
        ['url'=>'/wallet.html','name'=>'Vi tien'],
        ['url'=>'/profile.html','name'=>'Ca nhan'],
        ['url'=>'/map.html','name'=>'Ban do'],
        ['url'=>'/people.html','name'=>'Moi nguoi'],
    ];

    $results=[];
    foreach($pages as $p){
        $start=microtime(true);
        $ctx=stream_context_create(['http'=>['timeout'=>5,'ignore_errors'=>true]]);
        $html=@file_get_contents('https://shippershop.vn'.$p['url'],false,$ctx);
        $loadTime=round((microtime(true)-$start)*1000);
        $size=$html?strlen($html):0;
        $score=100;
        if($loadTime>1000) $score-=20;
        elseif($loadTime>500) $score-=10;
        if($size>200000) $score-=15;
        elseif($size>100000) $score-=5;
        $results[]=['url'=>$p['url'],'name'=>$p['name'],'load_ms'=>$loadTime,'size_kb'=>round($size/1024,1),'score'=>max(0,$score)];
    }

    usort($results,function($a,$b){return $b['score']-$a['score'];});
    $avgScore=count($results)?round(array_sum(array_column($results,'score'))/count($results)):0;
    $avgLoad=count($results)?round(array_sum(array_column($results,'load_ms'))/count($results)):0;

    return ['pages'=>$results,'avg_score'=>$avgScore,'avg_load_ms'=>$avgLoad,'total_pages'=>count($results)];
}, 1800);

ps_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
