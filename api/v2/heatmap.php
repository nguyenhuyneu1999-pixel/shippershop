<?php
// ShipperShop API v2 — Activity Heatmap
// GitHub-style activity grid (posts per day for past year)
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

$d=db();

function hm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$tid=intval($_GET['user_id']??0);
if(!$tid){$uid=optional_auth();$tid=$uid;}
if(!$tid) hm_ok('OK',[]);

$days=min(intval($_GET['days']??365),365);

$data=cache_remember('heatmap_'.$tid.'_'.$days, function() use($d,$tid,$days) {
    // Posts per day
    $activity=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM posts WHERE user_id=? AND `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at)",[$tid]);

    // Comments per day
    $comments=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM comments WHERE user_id=? AND `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at)",[$tid]);

    // Likes per day
    $likes=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM likes WHERE user_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at)",[$tid]);

    // Merge into daily map
    $dayMap=[];
    foreach($activity as $a) $dayMap[$a['day']]=($dayMap[$a['day']]??0)+intval($a['count'])*3; // posts weight 3
    foreach($comments as $c) $dayMap[$c['day']]=($dayMap[$c['day']]??0)+intval($c['count']); // comments weight 1
    foreach($likes as $l) $dayMap[$l['day']]=($dayMap[$l['day']]??0)+intval($l['count']); // likes weight 1

    // Fill all days
    $result=[];
    $maxVal=0;
    $totalActivity=0;
    $activeDays=0;
    for($i=$days-1;$i>=0;$i--){
        $day=date('Y-m-d',strtotime("-$i days"));
        $val=intval($dayMap[$day]??0);
        $result[]=['day'=>$day,'value'=>$val];
        if($val>$maxVal) $maxVal=$val;
        $totalActivity+=$val;
        if($val>0) $activeDays++;
    }

    // Longest streak
    $streak=0;$maxStreak=0;$currentStreak=0;
    for($i=count($result)-1;$i>=0;$i--){
        if($result[$i]['value']>0){$currentStreak++; if($currentStreak>$maxStreak) $maxStreak=$currentStreak;}
        else{$currentStreak=0;}
    }

    return [
        'days'=>$result,
        'max_value'=>$maxVal,
        'total_activity'=>$totalActivity,
        'active_days'=>$activeDays,
        'inactive_days'=>$days-$activeDays,
        'longest_streak'=>$maxStreak,
        'avg_per_active_day'=>$activeDays>0?round($totalActivity/$activeDays,1):0,
    ];
}, 600);

hm_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
