<?php
// ShipperShop API v2 — Admin KPI Dashboard
// Key performance indicators: conversion, activation, retention, revenue per user
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

function ak_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ak_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ak_fail('Admin only',403);

$data=cache_remember('admin_kpi', function() use($d) {
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $activated=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND total_posts>=1")['c']);
    $activationRate=$totalUsers>0?round($activated/$totalUsers*100,1):0;

    $wau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $mau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);
    $stickiness=$mau>0?round($wau/$mau*100,1):0;

    $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']);
    $arpu=$totalUsers>0?round($totalRevenue/$totalUsers):0;

    $avgPostsUser=$mau>0?round(intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c'])/$mau,1):0;

    $kpis=[
        ['name'=>'Activation Rate','value'=>$activationRate.'%','target'=>'50%','icon'=>'🎯','good'=>$activationRate>=50],
        ['name'=>'WAU/MAU Stickiness','value'=>$stickiness.'%','target'=>'30%','icon'=>'🔄','good'=>$stickiness>=30],
        ['name'=>'ARPU','value'=>number_format($arpu).'d','target'=>'500d','icon'=>'💰','good'=>$arpu>=500],
        ['name'=>'Avg Posts/User/Month','value'=>$avgPostsUser,'target'=>'3','icon'=>'📝','good'=>$avgPostsUser>=3],
        ['name'=>'MAU','value'=>$mau,'target'=>'100','icon'=>'👥','good'=>$mau>=100],
        ['name'=>'Total Users','value'=>$totalUsers,'target'=>'500','icon'=>'🌍','good'=>$totalUsers>=500],
    ];

    return ['kpis'=>$kpis,'summary'=>['total_users'=>$totalUsers,'activated'=>$activated,'mau'=>$mau,'wau'=>$wau,'revenue'=>$totalRevenue]];
}, 600);

ak_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
