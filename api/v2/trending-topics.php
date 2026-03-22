<?php
// ShipperShop API v2 — Trending Topics
// Discover what's popular: trending hashtags, hot posts, active discussions
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function tt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$hours=min(intval($_GET['hours']??24),168);

$data=cache_remember('trending_'.$hours, function() use($d,$hours) {
    $result=[];

    // Hot posts (most engaged in period)
    $result['hot_posts']=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY (p.likes_count*2+p.comments_count*3) DESC LIMIT 10");

    // Most discussed (by comments)
    $result['most_discussed']=$d->fetchAll("SELECT p.id,p.content,p.comments_count,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) AND p.comments_count>0 ORDER BY p.comments_count DESC LIMIT 5");

    // Rising users (most new followers)
    $result['rising_users']=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COUNT(f.id) as new_followers FROM follows f JOIN users u ON f.following_id=u.id WHERE f.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY f.following_id ORDER BY new_followers DESC LIMIT 5");

    // Active provinces
    $result['active_provinces']=$d->fetchAll("SELECT province,COUNT(*) as posts FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!='' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY province ORDER BY posts DESC LIMIT 10");

    // Top shipping companies (by post activity)
    $result['active_companies']=$d->fetchAll("SELECT u.shipping_company,COUNT(p.id) as posts FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND u.shipping_company IS NOT NULL AND u.shipping_company!='' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY u.shipping_company ORDER BY posts DESC LIMIT 10");

    $result['period_hours']=$hours;
    return $result;
}, 300);

tt_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
