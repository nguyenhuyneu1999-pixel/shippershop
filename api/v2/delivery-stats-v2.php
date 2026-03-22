<?php
// ShipperShop API v2 — Delivery Stats V2
// Advanced delivery analytics: success rate, avg time, peak hours, company breakdown
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$userId=intval($_GET['user_id']??0);
$days=min(intval($_GET['days']??30),365);
$action=$_GET['action']??'';

if(!$action||$action==='overview'){
    $cacheKey='dstats_'.($userId?:'all').'_'.$days;
    $data=cache_remember($cacheKey, function() use($d,$userId,$days) {
        $where="p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
        $params=[];
        if($userId){$where.=" AND p.user_id=?";$params[]=$userId;}

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts p WHERE $where",$params)['c']);
        $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(p.likes_count),0) as s FROM posts p WHERE $where",$params)['s']);
        $avgLikes=$total>0?round($totalLikes/$total,1):0;

        // By hour
        $byHour=$d->fetchAll("SELECT HOUR(p.created_at) as h, COUNT(*) as c FROM posts p WHERE $where GROUP BY HOUR(p.created_at) ORDER BY c DESC LIMIT 5",$params);

        // By company
        $byCompany=$d->fetchAll("SELECT u.shipping_company as company, COUNT(*) as posts, SUM(p.likes_count) as likes FROM posts p JOIN users u ON p.user_id=u.id WHERE $where AND u.shipping_company IS NOT NULL AND u.shipping_company!='' GROUP BY u.shipping_company ORDER BY posts DESC LIMIT 10",$params);

        // Daily trend
        $daily=$d->fetchAll("SELECT DATE(p.created_at) as day, COUNT(*) as posts FROM posts p WHERE $where GROUP BY DATE(p.created_at) ORDER BY day DESC LIMIT 14",$params);

        return ['total_posts'=>$total,'total_likes'=>$totalLikes,'avg_likes'=>$avgLikes,'peak_hours'=>$byHour,'by_company'=>$byCompany,'daily_trend'=>array_reverse($daily),'period_days'=>$days];
    }, 600);
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;
}

// Top performers
if($action==='top'){
    $top=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COUNT(p.id) as posts,SUM(p.likes_count) as likes FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY u.id ORDER BY posts DESC LIMIT 15");
    echo json_encode(['success'=>true,'data'=>$top],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
