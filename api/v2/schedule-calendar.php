<?php
// ShipperShop API v2 — Schedule Calendar
// Calendar view of scheduled/draft posts
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

function sc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$month=intval($_GET['month']??date('n'));
$year=intval($_GET['year']??date('Y'));

// Published posts this month
$published=$d->fetchAll("SELECT id,content,created_at,likes_count,comments_count,type FROM posts WHERE user_id=? AND `status`='active' AND MONTH(created_at)=? AND YEAR(created_at)=? ORDER BY created_at",[$uid,$month,$year]);

// Scheduled posts (from content_queue)
$scheduled=$d->fetchAll("SELECT id,content,scheduled_at,`status` FROM content_queue WHERE user_id=? AND `status` IN ('scheduled','draft') AND MONTH(scheduled_at)=? AND YEAR(scheduled_at)=? ORDER BY scheduled_at",[$uid,$month,$year]);

// Build calendar data
$days=[];
foreach($published as $p){
    $day=intval(date('j',strtotime($p['created_at'])));
    if(!isset($days[$day])) $days[$day]=['published'=>[],'scheduled'=>[]];
    $days[$day]['published'][]=['id'=>$p['id'],'preview'=>mb_substr($p['content'],0,60),'type'=>$p['type']??'post','likes'=>intval($p['likes_count']),'time'=>date('H:i',strtotime($p['created_at']))];
}
foreach($scheduled as $s){
    $day=intval(date('j',strtotime($s['scheduled_at'])));
    if(!isset($days[$day])) $days[$day]=['published'=>[],'scheduled'=>[]];
    $days[$day]['scheduled'][]=['id'=>$s['id'],'preview'=>mb_substr($s['content'],0,60),'status'=>$s['status'],'time'=>date('H:i',strtotime($s['scheduled_at']))];
}

$totalPublished=count($published);
$totalScheduled=count($scheduled);

sc_ok('OK',[
    'month'=>$month,'year'=>$year,
    'days'=>$days,
    'total_published'=>$totalPublished,
    'total_scheduled'=>$totalScheduled,
    'days_in_month'=>cal_days_in_month(CAL_GREGORIAN,$month,$year),
]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
