<?php
// ShipperShop API v2 — Admin Dashboard Widgets
// Quick stats, recent activity, pending actions for admin dashboard
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

function aw_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function aw_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') aw_fail('Admin only',403);

// Overview widget
if(!$action||$action==='overview'){
    $data=cache_remember('admin_overview', function() use($d) {
        return [
            'users_total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']),
            'users_today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()")['c']),
            'users_week'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']),
            'posts_total'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']),
            'posts_today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE created_at >= CURDATE() AND `status`='active'")['c']),
            'comments_today'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= CURDATE()")['c']),
            'online_now'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']),
            'revenue_total'=>intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0")['s']),
            'revenue_month'=>intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND amount>0 AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')")['s']),
        ];
    }, 120);
    aw_ok('OK',$data);
}

// Pending actions widget
if($action==='pending'){
    $data=[
        'deposits'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM payos_payments WHERE `status` IN ('pending','manual')")['c']),
        'reports'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM post_reports WHERE `status`='pending'")['c']),
        'verifications'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_verified=0 AND total_posts>=5")['c']),
    ];
    $data['total']=$data['deposits']+$data['reports']+$data['verifications'];
    aw_ok('OK',$data);
}

// Recent registrations
if($action==='recent_users'){
    $users=$d->fetchAll("SELECT id,fullname,avatar,email,shipping_company,created_at FROM users WHERE `status`='active' ORDER BY created_at DESC LIMIT 10");
    aw_ok('OK',$users);
}

// Top content today
if($action==='top_content'){
    $posts=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= CURDATE() ORDER BY (p.likes_count+p.comments_count) DESC LIMIT 5");
    aw_ok('OK',$posts);
}

// Growth chart data
if($action==='growth'){
    $days=min(intval($_GET['days']??30),90);
    $users=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
    $posts=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day");
    aw_ok('OK',['users'=>$users,'posts'=>$posts,'days'=>$days]);
}

aw_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
