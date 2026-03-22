<?php
// ShipperShop API v2 — Admin Summary
// One-page admin dashboard: key metrics, alerts, recent activity
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

function as2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function as2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {
$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') as2_fail('Admin only',403);

$data=cache_remember('admin_summary', function() use($d) {
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $newUsersToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()")['c']);
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $postsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
    $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed'")['s']);
    $activeNow=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
    $recentPosts=$d->fetchAll("SELECT p.id,LEFT(p.content,60) as preview,u.fullname,p.created_at FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' ORDER BY p.created_at DESC LIMIT 5");
    return ['users'=>$totalUsers,'new_today'=>$newUsersToday,'posts'=>$totalPosts,'posts_today'=>$postsToday,'revenue'=>$totalRevenue,'active_now'=>$activeNow,'recent'=>$recentPosts,'generated_at'=>date('c')];
}, 120);
as2_ok('OK',$data);
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
