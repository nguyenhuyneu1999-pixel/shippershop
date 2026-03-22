<?php
// ShipperShop API v2 — Content Calendar View
// Monthly calendar showing post activity per day
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

function cv_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$month=intval($_GET['month']??date('m'));
$year=intval($_GET['year']??date('Y'));
$userId=intval($_GET['user_id']??0);

$startDate=sprintf('%04d-%02d-01',$year,$month);
$endDate=date('Y-m-t',strtotime($startDate));

$where="p.`status`='active' AND p.created_at BETWEEN ? AND ?";
$params=[$startDate,$endDate.' 23:59:59'];
if($userId){$where.=" AND p.user_id=?";$params[]=$userId;}

$daily=$d->fetchAll("SELECT DATE(p.created_at) as day, COUNT(*) as posts, SUM(p.likes_count) as likes, SUM(p.comments_count) as comments FROM posts p WHERE $where GROUP BY DATE(p.created_at) ORDER BY day",$params);

// Build calendar grid
$daysInMonth=intval(date('t',strtotime($startDate)));
$firstDow=intval(date('N',strtotime($startDate))); // 1=Mon
$calendar=[];
$dailyMap=[];foreach($daily as $dd){$dailyMap[$dd['day']]=$dd;}

for($day=1;$day<=$daysInMonth;$day++){
    $dateStr=sprintf('%04d-%02d-%02d',$year,$month,$day);
    $data=$dailyMap[$dateStr]??null;
    $calendar[]=['day'=>$day,'date'=>$dateStr,'posts'=>intval($data['posts']??0),'likes'=>intval($data['likes']??0),'comments'=>intval($data['comments']??0),'dow'=>intval(date('N',strtotime($dateStr)))];
}

$totalPosts=array_sum(array_column($daily,'posts'));
$totalLikes=array_sum(array_column($daily,'likes'));
$activeDays=count($daily);

cv_ok('OK',['calendar'=>$calendar,'month'=>$month,'year'=>$year,'first_dow'=>$firstDow,'days_in_month'=>$daysInMonth,'total_posts'=>$totalPosts,'total_likes'=>$totalLikes,'active_days'=>$activeDays]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
