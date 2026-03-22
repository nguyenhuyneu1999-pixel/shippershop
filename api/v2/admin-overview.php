<?php
// ShipperShop API v2 — Admin Overview
// Complete admin dashboard: all key metrics in one call
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

function ao2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ao2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ao2_fail('Admin only',403);

$data=cache_remember('admin_overview_v2', function() use($d) {
    // Users
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $newToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()")['c']);
    $dau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= CURDATE()")['c']);
    $mau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']);

    // Content
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $postsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
    $totalGroups=intval($d->fetchOne("SELECT COUNT(*) as c FROM groups")['c']);
    $totalComments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']);

    // Revenue
    $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed'")['s']);
    $subscribers=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2")['c']);

    // System
    $dbSize=$d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1048576,1) as mb FROM information_schema.tables WHERE table_schema=DATABASE()");
    $tableCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()")['c']);
    $apiCount=count(glob(__DIR__.'/*.php'))-1;

    return ['users'=>['total'=>$totalUsers,'new_today'=>$newToday,'dau'=>$dau,'mau'=>$mau],'content'=>['posts'=>$totalPosts,'posts_today'=>$postsToday,'groups'=>$totalGroups,'comments'=>$totalComments],'revenue'=>['total'=>$totalRevenue,'subscribers'=>$subscribers],'system'=>['db_mb'=>floatval($dbSize['mb']??0),'tables'=>$tableCount,'apis'=>$apiCount,'php'=>phpversion()],'generated_at'=>date('c')];
}, 120);

ao2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
