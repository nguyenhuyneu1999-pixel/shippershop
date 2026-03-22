<?php
// ShipperShop API v2 — User KPI Dashboard
// Personal KPI tracking: delivery targets, income goals, rating targets
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

function kpi_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $key='kpi_targets_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $targets=$row?json_decode($row['value'],true):['daily_posts'=>5,'weekly_likes'=>50,'monthly_followers'=>10,'rating_target'=>4.0];

    // Current progress
    $todayPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= CURDATE()",[$uid])['c']);
    $weekLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['s']);
    $monthFollowers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$uid])['c']);

    $kpis=[
        ['id'=>'daily_posts','name'=>'Bai/ngay','target'=>intval($targets['daily_posts']??5),'current'=>$todayPosts,'icon'=>'📝','period'=>'Hom nay'],
        ['id'=>'weekly_likes','name'=>'Likes/tuan','target'=>intval($targets['weekly_likes']??50),'current'=>$weekLikes,'icon'=>'❤️','period'=>'7 ngay'],
        ['id'=>'monthly_followers','name'=>'Followers/thang','target'=>intval($targets['monthly_followers']??10),'current'=>$monthFollowers,'icon'=>'👥','period'=>'30 ngay'],
    ];

    foreach($kpis as &$k){
        $k['progress']=min(100,$k['target']>0?round($k['current']/$k['target']*100):0);
        $k['status']=$k['progress']>=100?'achieved':($k['progress']>=50?'on_track':'behind');
    }unset($k);

    $achieved=count(array_filter($kpis,function($k){return $k['status']==='achieved';}));
    $overallScore=count($kpis)>0?round($achieved/count($kpis)*100):0;

    kpi_ok('OK',['kpis'=>$kpis,'targets'=>$targets,'achieved'=>$achieved,'total'=>count($kpis),'overall_score'=>$overallScore]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $key='kpi_targets_'.$uid;
    $targets=['daily_posts'=>max(1,min(50,intval($input['daily_posts']??5))),'weekly_likes'=>max(1,min(500,intval($input['weekly_likes']??50))),'monthly_followers'=>max(1,min(100,intval($input['monthly_followers']??10))),'rating_target'=>max(1,min(5,floatval($input['rating_target']??4)))];
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($targets),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($targets)]);
    kpi_ok('Da cap nhat KPI!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
