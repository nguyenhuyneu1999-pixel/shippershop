<?php
// ShipperShop API v2 — Admin Real-time Monitor
// Live platform activity: recent posts, logins, actions in last 5 min
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function rm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rm_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') rm_fail('Admin only',403);

$minutes=min(intval($_GET['minutes']??5),60);

// Recent posts
$recentPosts=$d->fetchAll("SELECT p.id,p.content,p.created_at,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE) ORDER BY p.created_at DESC LIMIT 10");

// Recent comments
$recentComments=$d->fetchAll("SELECT c.content,c.created_at,u.fullname FROM comments c JOIN users u ON c.user_id=u.id WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE) ORDER BY c.created_at DESC LIMIT 10");

// Recent likes
$recentLikes=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE)")['c']);

// Online users
$online=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']);

// Active now (posted in last hour)
$activeNow=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);

// Recent logins
$recentLogins=intval($d->fetchOne("SELECT COUNT(*) as c FROM login_attempts WHERE success=1 AND created_at >= DATE_SUB(NOW(), INTERVAL $minutes MINUTE)")['c']);

rm_ok('OK',['posts'=>$recentPosts,'comments'=>$recentComments,'likes_count'=>$recentLikes,'online'=>$online,'active_now'=>$activeNow,'logins'=>$recentLogins,'window_minutes'=>$minutes,'timestamp'=>date('c')]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
