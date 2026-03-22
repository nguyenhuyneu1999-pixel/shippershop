<?php
// ShipperShop API v2 — Post Best Time
// Recommend best posting times based on historical engagement data
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
$data=cache_remember('best_time_'.($userId?:'all'), function() use($d,$userId) {
    $where="p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $params=[];
    if($userId){$where.=" AND p.user_id=?";$params[]=$userId;}
    $byHour=$d->fetchAll("SELECT HOUR(p.created_at) as h, COUNT(*) as posts, AVG(p.likes_count+p.comments_count) as avg_eng FROM posts p WHERE $where GROUP BY HOUR(p.created_at) ORDER BY avg_eng DESC",$params);
    $byDay=$d->fetchAll("SELECT DAYOFWEEK(p.created_at) as dow, COUNT(*) as posts, AVG(p.likes_count+p.comments_count) as avg_eng FROM posts p WHERE $where GROUP BY DAYOFWEEK(p.created_at) ORDER BY avg_eng DESC",$params);
    $dayNames=['','CN','T2','T3','T4','T5','T6','T7'];
    foreach($byDay as &$bd){$bd['day_name']=$dayNames[intval($bd['dow'])]??'';}unset($bd);
    $bestHour=$byHour[0]??null;$bestDay=$byDay[0]??null;
    return ['by_hour'=>$byHour,'by_day'=>$byDay,'best_hour'=>$bestHour?intval($bestHour['h']):20,'best_day'=>$bestDay?$bestDay['day_name']:'T2','recommendation'=>'Dang bai luc '.($bestHour?$bestHour['h']:20).'h vao '.($bestDay?$bestDay['day_name']:'T2')];
}, 1800);
echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
