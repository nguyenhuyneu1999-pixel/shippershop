<?php
// ShipperShop API v2 — Admin SEO Monitor
// Track page meta, indexation status, performance scores
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

function sm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sm2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') sm2_fail('Admin only',403);

$data=cache_remember('seo_monitor', function() use($d) {
    $pages=[
        ['url'=>'/','title'=>'Trang chu','has_meta'=>true,'has_og'=>true],
        ['url'=>'/login.html','title'=>'Dang nhap','has_meta'=>true,'has_og'=>false],
        ['url'=>'/register.html','title'=>'Dang ky','has_meta'=>true,'has_og'=>false],
        ['url'=>'/marketplace.html','title'=>'Cho mua ban','has_meta'=>true,'has_og'=>true],
        ['url'=>'/groups.html','title'=>'Hoi nhom','has_meta'=>true,'has_og'=>false],
        ['url'=>'/traffic.html','title'=>'Giao thong','has_meta'=>true,'has_og'=>false],
        ['url'=>'/wallet.html','title'=>'Vi tien','has_meta'=>true,'has_og'=>false],
        ['url'=>'/messages.html','title'=>'Tin nhan','has_meta'=>false,'has_og'=>false],
        ['url'=>'/map.html','title'=>'Ban do','has_meta'=>false,'has_og'=>false],
        ['url'=>'/profile.html','title'=>'Ca nhan','has_meta'=>true,'has_og'=>false],
    ];

    // Score each page
    foreach($pages as &$p){
        $score=40; // base
        if($p['has_meta']) $score+=30;
        if($p['has_og']) $score+=20;
        $score+=10; // SSL bonus
        $p['score']=$score;
    }unset($p);

    $avgScore=count($pages)?round(array_sum(array_column($pages,'score'))/count($pages)):0;
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $indexablePages=count($pages)+$totalPosts;

    $issues=[];
    foreach($pages as $p){if(!$p['has_og']) $issues[]=$p['url'].' - thieu Open Graph';}

    return ['pages'=>$pages,'avg_score'=>$avgScore,'indexable'=>$indexablePages,'total_posts'=>$totalPosts,'issues'=>$issues,'issue_count'=>count($issues)];
}, 1800);

sm2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
