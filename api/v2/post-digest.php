<?php
// ShipperShop API v2 — Post Digest
// Curated daily/weekly digest of best content for email or in-app
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();$period=$_GET['period']??'daily';

function pdg_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$hours=$period==='weekly'?168:24;

$data=cache_remember('post_digest_'.$period, function() use($d,$hours,$period) {
    // Top posts by engagement
    $top=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,p.shares_count,p.created_at,u.fullname,u.avatar,u.shipping_company FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY (p.likes_count*2+p.comments_count*3+p.shares_count) DESC LIMIT 10");

    // Most commented
    $discussed=$d->fetchAll("SELECT p.id,p.content,p.comments_count,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.comments_count>0 AND p.created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR) ORDER BY p.comments_count DESC LIMIT 5");

    // New users this period
    $newUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)")['c']);

    // Stats
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)")['c']);
    $totalComments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)")['c']);
    $totalLikes=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL $hours HOUR)")['c']);

    return ['period'=>$period,'hours'=>$hours,'top_posts'=>$top,'most_discussed'=>$discussed,'stats'=>['posts'=>$totalPosts,'comments'=>$totalComments,'likes'=>$totalLikes,'new_users'=>$newUsers],'generated_at'=>date('c')];
}, 600);

pdg_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
