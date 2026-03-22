<?php
// ShipperShop API v2 — Analytics Export
// Export post/user analytics as JSON for charts and reports
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

$d=db();$action=$_GET['action']??'';

function ae_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$days=min(intval($_GET['days']??30),365);

if(!$action||$action==='my_posts'){
    $posts=$d->fetchAll("SELECT id,content,likes_count,comments_count,shares_count,view_count,created_at FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) ORDER BY created_at DESC",[$uid]);
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as posts,SUM(likes_count) as likes,SUM(comments_count) as comments FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day",[$uid]);
    ae_ok('OK',['posts'=>$posts,'daily'=>$daily,'total_posts'=>count($posts),'period_days'=>$days,'exported_at'=>date('c')]);
}

if($action==='my_engagement'){
    $data=['likes_given'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['c']),
           'comments_made'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['c']),
           'likes_received'=>intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['s']),
           'followers_gained'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['c']),
           'period_days'=>$days];
    ae_ok('OK',$data);
}

if($action==='growth'){
    $weekly=$d->fetchAll("SELECT YEARWEEK(created_at) as week,COUNT(*) as posts,SUM(likes_count) as likes FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY YEARWEEK(created_at) ORDER BY week",[$uid]);
    ae_ok('OK',['weekly'=>$weekly,'period_days'=>$days]);
}

ae_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
