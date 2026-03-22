<?php
// ShipperShop API v2 — Content Calendar V2
// Visual content calendar: daily post counts, scheduled, gaps, best days
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

function ccv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$month=$_GET['month']??date('Y-m');
$startDate=$month.'-01';
$endDate=date('Y-m-t',strtotime($startDate));

$data=cache_remember('content_cal_v2_'.$uid.'_'.$month, function() use($d,$uid,$startDate,$endDate,$month) {
    $posts=$d->fetchAll("SELECT DATE(created_at) as day, COUNT(*) as posts, SUM(likes_count) as likes, SUM(comments_count) as comments FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at)",[$uid,$startDate,$endDate.' 23:59:59']);

    $dayMap=[];foreach($posts as $p){$dayMap[$p['day']]=$p;}

    // Build calendar grid
    $firstDow=intval(date('N',strtotime($startDate))); // 1=Mon
    $daysInMonth=intval(date('t',strtotime($startDate)));
    $calendar=[];$totalPosts=0;$activeDays=0;$bestDay=null;$bestEng=0;
    for($day=1;$day<=$daysInMonth;$day++){
        $dateStr=$month.'-'.str_pad($day,2,'0',STR_PAD_LEFT);
        $data=$dayMap[$dateStr]??null;
        $posts=$data?intval($data['posts']):0;
        $likes=$data?intval($data['likes']):0;
        $comments=$data?intval($data['comments']):0;
        $eng=$likes+$comments;
        $totalPosts+=$posts;
        if($posts>0) $activeDays++;
        if($eng>$bestEng){$bestEng=$eng;$bestDay=$dateStr;}
        $isPast=strtotime($dateStr)<strtotime(date('Y-m-d'));
        $isToday=$dateStr===date('Y-m-d');
        $calendar[]=['date'=>$dateStr,'day'=>$day,'dow'=>intval(date('N',strtotime($dateStr))),'posts'=>$posts,'likes'=>$likes,'comments'=>$comments,'engagement'=>$eng,'is_past'=>$isPast,'is_today'=>$isToday,'has_gap'=>$isPast&&$posts===0];
    }

    $gapDays=count(array_filter($calendar,function($c){return $c['has_gap'];}));
    $consistency=$daysInMonth>0?round($activeDays/min($daysInMonth,intval(date('j')))*100):0;

    return ['calendar'=>$calendar,'month'=>$month,'total_posts'=>$totalPosts,'active_days'=>$activeDays,'gap_days'=>$gapDays,'best_day'=>$bestDay,'best_engagement'=>$bestEng,'consistency'=>$consistency,'first_dow'=>$firstDow];
}, 300);

ccv2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
