<?php
// ShipperShop API v2 — Engagement Heatmap
// Hour × Day-of-week heatmap of post engagement
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

function eh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$days=min(intval($_GET['days']??30),365);
$userId=intval($_GET['user_id']??0);

$data=cache_remember('eng_heatmap_'.($userId?:'all').'_'.$days, function() use($d,$days,$userId) {
    $where="p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)";
    $params=[];
    if($userId){$where.=" AND p.user_id=?";$params[]=$userId;}

    $cells=$d->fetchAll("SELECT DAYOFWEEK(p.created_at) as dow, HOUR(p.created_at) as h, COUNT(*) as posts, AVG(p.likes_count+p.comments_count) as avg_eng FROM posts p WHERE $where GROUP BY DAYOFWEEK(p.created_at), HOUR(p.created_at)",$params);

    // Build 7x24 grid
    $grid=[];$maxEng=0;
    foreach($cells as $c){
        $dow=intval($c['dow']);$h=intval($c['h']);
        $eng=round(floatval($c['avg_eng']),1);
        $grid[$dow.'_'.$h]=['dow'=>$dow,'hour'=>$h,'posts'=>intval($c['posts']),'avg_engagement'=>$eng];
        if($eng>$maxEng) $maxEng=$eng;
    }

    // Best slots
    usort($cells,function($a,$b){return floatval($b['avg_eng'])-floatval($a['avg_eng']);});
    $best=array_slice($cells,0,5);
    $dayNames=['','CN','T2','T3','T4','T5','T6','T7'];
    foreach($best as &$b){$b['day_name']=$dayNames[intval($b['dow'])]??'';$b['avg_eng']=round(floatval($b['avg_eng']),1);}unset($b);

    return ['grid'=>array_values($grid),'best_slots'=>$best,'max_engagement'=>$maxEng,'period_days'=>$days];
}, 600);

eh_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
