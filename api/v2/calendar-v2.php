<?php
// ShipperShop API v2 — Content Calendar V2
// Enhanced calendar: scheduled posts, events, milestones, streaks
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

function cv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$month=intval($_GET['month']??date('m'));
$year=intval($_GET['year']??date('Y'));
$startDate=sprintf('%04d-%02d-01',$year,$month);
$endDate=date('Y-m-t',strtotime($startDate)).' 23:59:59';

// Posts per day
$posts=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as count, SUM(likes_count) as likes FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at)",[$uid,$startDate,$endDate]);
$postMap=[];foreach($posts as $p){$postMap[$p['day']]=$p;}

// Scheduled content
$scheduled=$d->fetchAll("SELECT DATE(scheduled_at) as day, COUNT(*) as count FROM content_queue WHERE user_id=? AND `status`='scheduled' AND scheduled_at BETWEEN ? AND ? GROUP BY DATE(scheduled_at)",[$uid,$startDate,$endDate]);
$schedMap=[];foreach($scheduled as $s){$schedMap[$s['day']]=intval($s['count']);}

// Build calendar
$daysInMonth=intval(date('t',strtotime($startDate)));
$calendar=[];$streak=0;$maxStreak=0;$activeDays=0;
for($day=1;$day<=$daysInMonth;$day++){
    $dateStr=sprintf('%04d-%02d-%02d',$year,$month,$day);
    $pd=$postMap[$dateStr]??null;
    $sc=$schedMap[$dateStr]??0;
    $hasPost=$pd&&intval($pd['count'])>0;
    if($hasPost){$streak++;$activeDays++;if($streak>$maxStreak)$maxStreak=$streak;}else{$streak=0;}
    $calendar[]=['day'=>$day,'date'=>$dateStr,'posts'=>intval($pd['count']??0),'likes'=>intval($pd['likes']??0),'scheduled'=>$sc,'has_activity'=>$hasPost||$sc>0];
}

$totalPosts=array_sum(array_column($posts,'count'));
$totalLikes=array_sum(array_column($posts,'likes'));

cv2_ok('OK',['calendar'=>$calendar,'month'=>$month,'year'=>$year,'stats'=>['total_posts'=>$totalPosts,'total_likes'=>$totalLikes,'active_days'=>$activeDays,'max_streak'=>$maxStreak,'scheduled_count'=>array_sum($schedMap)]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
