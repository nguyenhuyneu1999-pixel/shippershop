<?php
// ShipperShop API v2 — Post Calendar
// Calendar view of scheduled/published posts, streak data
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

$d=db();$action=$_GET['action']??'';

function cal_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$month=intval($_GET['month']??date('n'));
$year=intval($_GET['year']??date('Y'));
if($month<1||$month>12) $month=date('n');
if($year<2024||$year>2030) $year=date('Y');

$startDate="$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-01";
$endDate=date('Y-m-t',strtotime($startDate))." 23:59:59";

// Monthly view — posts per day
if(!$action||$action==='month'){
    // Published posts
    $published=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count,SUM(likes_count) as likes FROM posts WHERE user_id=? AND `status`='active' AND is_draft=0 AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at)",[$uid,$startDate,$endDate]);

    // Scheduled posts
    $scheduled=$d->fetchAll("SELECT id,content,scheduled_at FROM posts WHERE user_id=? AND is_draft=0 AND scheduled_at IS NOT NULL AND scheduled_at BETWEEN ? AND ? ORDER BY scheduled_at",[$uid,$startDate,$endDate]);

    // Drafts
    $drafts=$d->fetchAll("SELECT id,content,created_at FROM posts WHERE user_id=? AND is_draft=1 ORDER BY created_at DESC LIMIT 10",[$uid]);

    // Build day map
    $dayMap=[];
    foreach($published as $p) $dayMap[$p['day']]=['published'=>intval($p['count']),'likes'=>intval($p['likes'])];
    foreach($scheduled as $s){
        $day=substr($s['scheduled_at'],0,10);
        if(!isset($dayMap[$day])) $dayMap[$day]=['published'=>0,'likes'=>0];
        $dayMap[$day]['scheduled'][]=[ 'id'=>intval($s['id']),'content'=>mb_substr($s['content']??'',0,60),'time'=>substr($s['scheduled_at'],11,5)];
    }

    // Monthly stats
    $totalPosts=array_sum(array_column($published,'count'));
    $totalLikes=array_sum(array_column($published,'likes'));
    $activeDays=count($published);

    cal_ok('OK',[
        'month'=>$month,'year'=>$year,
        'days'=>$dayMap,
        'stats'=>['posts'=>$totalPosts,'likes'=>$totalLikes,'active_days'=>$activeDays,'scheduled'=>count($scheduled),'drafts'=>count($drafts)],
        'drafts'=>$drafts,
    ]);
}

// Day detail
if($action==='day'){
    $day=$_GET['day']??date('Y-m-d');
    $posts=$d->fetchAll("SELECT id,content,images,likes_count,comments_count,created_at FROM posts WHERE user_id=? AND `status`='active' AND DATE(created_at)=? ORDER BY created_at",[$uid,$day]);
    $scheduled=$d->fetchAll("SELECT id,content,scheduled_at FROM posts WHERE user_id=? AND is_draft=0 AND scheduled_at IS NOT NULL AND DATE(scheduled_at)=?",[$uid,$day]);
    cal_ok('OK',['day'=>$day,'posts'=>$posts,'scheduled'=>$scheduled]);
}

cal_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
