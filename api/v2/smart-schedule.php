<?php
// ShipperShop API v2 — Smart Schedule
// AI-powered optimal posting time based on historical engagement data
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

$d=db();

function ss2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=optional_auth();
$action=$_GET['action']??'';

// Optimal times based on platform-wide engagement
if(!$action||$action==='optimal'){
    $data=cache_remember('smart_schedule'.($uid?'_'.$uid:''), function() use($d,$uid) {
        // Platform-wide best hours
        $hourStats=$d->fetchAll("SELECT HOUR(p.created_at) as h, AVG(p.likes_count+p.comments_count) as avg_eng, COUNT(*) as posts FROM posts p WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY HOUR(p.created_at) HAVING posts >= 3 ORDER BY avg_eng DESC");

        // Best days of week
        $dayStats=$d->fetchAll("SELECT DAYOFWEEK(p.created_at) as dow, AVG(p.likes_count+p.comments_count) as avg_eng, COUNT(*) as posts FROM posts p WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DAYOFWEEK(p.created_at) ORDER BY avg_eng DESC");

        $dayNames=['','CN','T2','T3','T4','T5','T6','T7'];
        foreach($dayStats as &$ds){$ds['day_name']=$dayNames[intval($ds['dow'])]??'';}unset($ds);

        // User-specific best times
        $userBest=[];
        if($uid){
            $userBest=$d->fetchAll("SELECT HOUR(created_at) as h, AVG(likes_count+comments_count) as avg_eng FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) GROUP BY HOUR(created_at) HAVING COUNT(*)>=2 ORDER BY avg_eng DESC LIMIT 3",[$uid]);
        }

        // Recommended slots
        $slots=[];
        foreach(array_slice($hourStats,0,5) as $hs){
            $h=intval($hs['h']);
            $slots[]=['time'=>sprintf('%02d:00',$h),'score'=>round(floatval($hs['avg_eng']),1),'label'=>$h>=6&&$h<12?'Sang':($h>=12&&$h<18?'Chieu':'Toi')];
        }

        return ['recommended_slots'=>$slots,'hour_stats'=>$hourStats,'day_stats'=>$dayStats,'user_best'=>$userBest,'analysis_period'=>'30 ngay'];
    }, 3600);
    ss2_ok('OK',$data);
}

// Next best time to post
if($action==='next'){
    $hourStats=$d->fetchAll("SELECT HOUR(created_at) as h, AVG(likes_count+comments_count) as avg_eng FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY) GROUP BY HOUR(created_at) ORDER BY avg_eng DESC LIMIT 5");
    $now=intval(date('H'));
    $nextBest=null;
    foreach($hourStats as $hs){
        $h=intval($hs['h']);
        if($h>$now){$nextBest=$hs;break;}
    }
    if(!$nextBest&&$hourStats) $nextBest=$hourStats[0];
    ss2_ok('OK',['next_time'=>$nextBest?sprintf('%02d:00',intval($nextBest['h'])):'08:00','expected_engagement'=>round(floatval($nextBest['avg_eng']??0),1),'current_hour'=>$now]);
}

ss2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
