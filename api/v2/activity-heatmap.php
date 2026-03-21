<?php
// ShipperShop API v2 — Activity Heatmap Data
// GitHub-style contribution heatmap for user profiles
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$userId=intval($_GET['user_id']??0);
$days=min(intval($_GET['days']??365),365);
if(!$userId){echo json_encode(['success'=>true,'data'=>['days'=>[]]]);exit;}

$data=cache_remember('heatmap_'.$userId.'_'.$days, function() use($d,$userId,$days) {
    // Posts per day
    $posts=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at)",[$userId]);
    // Comments per day
    $comments=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM comments WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at)",[$userId]);

    // Merge into day map
    $dayMap=[];
    foreach($posts as $p){$dayMap[$p['day']]=intval($p['count']);}
    foreach($comments as $c){$dayMap[$c['day']]=(isset($dayMap[$c['day']])?$dayMap[$c['day']]:0)+intval($c['count']);}

    // Fill all days
    $result=[];$maxVal=0;$totalContrib=0;$activeDays=0;
    for($i=$days-1;$i>=0;$i--){
        $date=date('Y-m-d',strtotime("-$i days"));
        $val=$dayMap[$date]??0;
        $result[]=['date'=>$date,'count'=>$val,'level'=>$val>=10?4:($val>=5?3:($val>=2?2:($val>=1?1:0)))];
        if($val>$maxVal) $maxVal=$val;
        $totalContrib+=$val;
        if($val>0) $activeDays++;
    }

    return [
        'days'=>$result,
        'total_contributions'=>$totalContrib,
        'active_days'=>$activeDays,
        'max_day_count'=>$maxVal,
        'streak'=>$activeDays>0?min($activeDays,intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$userId])['current_streak']??0)):0,
    ];
}, 600);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
