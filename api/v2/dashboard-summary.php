<?php
// ShipperShop API v2 — Dashboard Summary
// Lightweight aggregated stats for admin dashboard cards
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

function ds_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ds_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') ds_fail('Admin only',403);

$data=cache_remember('dashboard_summary', function() use($d) {
    // Users
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $newUsersToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at>=CURDATE()")['c']);
    $newUsersWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")['c']);
    $onlineNow=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1 AND last_active>=DATE_SUB(NOW(),INTERVAL 5 MINUTE)")['c']);

    // Posts
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $postsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at>=CURDATE()")['c']);
    $postsWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)")['c']);

    // Engagement
    $likesToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE created_at>=CURDATE()")['c']);
    $commentsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE `status`='active' AND created_at>=CURDATE()")['c']);

    // Revenue
    $revenueMonth=floatval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='subscription' AND created_at>=DATE_FORMAT(NOW(),'%Y-%m-01')")['s']);
    $pendingDeposits=intval($d->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE type='deposit' AND `status`='pending'")['c']);

    // Reports
    $pendingReports=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE resolved_by IS NULL")['c']);

    // Content queue
    $pendingQueue=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='pending'")['c']);

    // Groups
    $totalGroups=intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c']);

    // Messages today
    $msgsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE created_at>=CURDATE()")['c']);

    return [
        'users'=>['total'=>$totalUsers,'today'=>$newUsersToday,'week'=>$newUsersWeek,'online'=>$onlineNow],
        'posts'=>['total'=>$totalPosts,'today'=>$postsToday,'week'=>$postsWeek],
        'engagement'=>['likes_today'=>$likesToday,'comments_today'=>$commentsToday,'messages_today'=>$msgsToday],
        'revenue'=>['month'=>$revenueMonth,'pending_deposits'=>$pendingDeposits],
        'moderation'=>['pending_reports'=>$pendingReports,'pending_queue'=>$pendingQueue],
        'groups'=>$totalGroups,
        'generated_at'=>date('Y-m-d H:i:s'),
    ];
}, 120);

ds_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
