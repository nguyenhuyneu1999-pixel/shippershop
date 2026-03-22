<?php
// ShipperShop API v2 — Content Calendar
// Monthly calendar view of all content (posts, scheduled, drafts)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function cc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$month=$_GET['month']??date('Y-m');
$startDate=$month.'-01';
$endDate=date('Y-m-t',strtotime($startDate));

// Published posts
$posts=$d->fetchAll("SELECT id,content,likes_count,comments_count,DATE(created_at) as day,'published' as type FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN ? AND ?",[$uid,$startDate,$endDate.' 23:59:59']);

// Scheduled
$scheduled=$d->fetchAll("SELECT id,content,DATE(scheduled_at) as day,'scheduled' as type FROM content_queue WHERE user_id=? AND `status`='scheduled' AND scheduled_at BETWEEN ? AND ?",[$uid,$startDate,$endDate.' 23:59:59']);

// Drafts (by created date)
$drafts=$d->fetchAll("SELECT id,content,DATE(created_at) as day,'draft' as type FROM content_queue WHERE user_id=? AND `status`='draft' AND created_at BETWEEN ? AND ?",[$uid,$startDate,$endDate.' 23:59:59']);

// Group by day
$calendar=[];
$allItems=array_merge($posts,$scheduled,$drafts);
foreach($allItems as $item){
    $day=$item['day'];
    if(!isset($calendar[$day])) $calendar[$day]=[];
    $calendar[$day][]=$item;
}

// Stats
$totalPosts=count($posts);
$totalScheduled=count($scheduled);
$totalDrafts=count($drafts);
$daysActive=count(array_unique(array_column($posts,'day')));

cc_ok('OK',[
    'month'=>$month,
    'calendar'=>$calendar,
    'stats'=>['posts'=>$totalPosts,'scheduled'=>$totalScheduled,'drafts'=>$totalDrafts,'days_active'=>$daysActive],
    'start_date'=>$startDate,
    'end_date'=>$endDate,
]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
