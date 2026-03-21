<?php
// ShipperShop API v2 — Admin Stats Cache
// Pre-computed dashboard stats, refreshed by cron
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

$d=db();$action=$_GET['action']??'';

function as_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function as_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') as_fail('Admin only',403);

// Dashboard overview (heavy cached)
if(!$action||$action==='dashboard'){
    $data=cache_remember('admin_dashboard', function() use($d) {
        $users=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
        $usersToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at > CURDATE()")['c']);
        $usersWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
        $posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
        $postsToday=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at > CURDATE()")['c']);
        $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']);
        $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups`")['c']);
        $messages=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages")['c']);
        $revenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']);
        $revenueMonth=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)")['s']);
        $activeSubs=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE `status`='active' AND expires_at > NOW()")['c']);
        $pendingReports=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);
        $pendingDeposits=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);

        // Growth trends (7-day)
        $dailyUsers=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM users WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");
        $dailyPosts=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM posts WHERE `status`='active' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day");

        // Top active users
        $topUsers=$d->fetchAll("SELECT id,fullname,avatar,total_posts,total_success FROM users WHERE `status`='active' ORDER BY total_posts DESC LIMIT 5");

        return [
            'users'=>['total'=>$users,'today'=>$usersToday,'week'=>$usersWeek],
            'posts'=>['total'=>$posts,'today'=>$postsToday],
            'comments'=>$comments,
            'groups'=>$groups,
            'messages'=>$messages,
            'revenue'=>['total'=>$revenue,'month'=>$revenueMonth],
            'active_subs'=>$activeSubs,
            'pending'=>['reports'=>$pendingReports,'deposits'=>$pendingDeposits],
            'trends'=>['users'=>$dailyUsers,'posts'=>$dailyPosts],
            'top_users'=>$topUsers,
            'cached_at'=>date('c'),
        ];
    }, 300); // 5 min cache

    as_ok('OK',$data);
}

// Quick stats (lightweight)
if($action==='quick'){
    $pending=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']);
    $deposits=intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']);
    $online=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']);
    as_ok('OK',['pending_reports'=>$pending,'pending_deposits'=>$deposits,'online_users'=>$online]);
}

as_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
