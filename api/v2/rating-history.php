<?php
// ShipperShop API v2 — Rating History
// Track user's delivery rating over time with trend analysis
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

function rh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$targetId=intval($_GET['user_id']??$uid);

$key='ratings_for_'.$targetId;
$row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
$allRatings=$row?json_decode($row['value'],true):[];

// Monthly averages
$monthly=[];
foreach($allRatings as $r){
    $month=substr($r['created_at']??'',0,7);
    if(!$month) continue;
    if(!isset($monthly[$month])) $monthly[$month]=['scores'=>[],'count'=>0];
    $avg=0;$cnt=0;
    foreach($r['scores']??[] as $s){$avg+=intval($s);$cnt++;}
    if($cnt>0) $monthly[$month]['scores'][]=round($avg/$cnt,1);
    $monthly[$month]['count']++;
}

$trend=[];
foreach($monthly as $month=>$data){
    $avgScore=count($data['scores'])?round(array_sum($data['scores'])/count($data['scores']),1):0;
    $trend[]=['month'=>$month,'avg_score'=>$avgScore,'reviews'=>$data['count']];
}
usort($trend,function($a,$b){return strcmp($a['month'],$b['month']);});

// Overall trend direction
$trendDir='stable';
if(count($trend)>=2){
    $last=$trend[count($trend)-1]['avg_score'];
    $prev=$trend[count($trend)-2]['avg_score'];
    if($last>$prev+0.3) $trendDir='up';
    elseif($last<$prev-0.3) $trendDir='down';
}

// Best/worst categories
$catTotals=[];
foreach($allRatings as $r){
    foreach($r['scores']??[] as $cat=>$score){
        if(!isset($catTotals[$cat])) $catTotals[$cat]=['sum'=>0,'count'=>0];
        $catTotals[$cat]['sum']+=intval($score);$catTotals[$cat]['count']++;
    }
}
$catAvgs=[];
foreach($catTotals as $cat=>$data){$catAvgs[$cat]=$data['count']>0?round($data['sum']/$data['count'],1):0;}
arsort($catAvgs);
$best=array_slice(array_keys($catAvgs),0,1);
$worst=array_slice(array_keys(array_reverse($catAvgs,true)),0,1);

rh_ok('OK',['trend'=>$trend,'trend_direction'=>$trendDir,'total_reviews'=>count($allRatings),'category_averages'=>$catAvgs,'best_category'=>$best[0]??'','worst_category'=>$worst[0]??'']);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
