<?php
// ShipperShop API v2 — Admin Growth Metrics
// Comprehensive platform growth: WoW, MoM, DAU/WAU/MAU trends
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

function gm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function gm2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') gm2_fail('Admin only',403);

$data=cache_remember('growth_metrics_v2', function() use($d) {
    // User growth
    $usersThisWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $usersLastWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $userWoW=$usersLastWeek>0?round(($usersThisWeek-$usersLastWeek)/$usersLastWeek*100,1):($usersThisWeek>0?100:0);

    // Post growth
    $postsThisWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $postsLastWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $postWoW=$postsLastWeek>0?round(($postsThisWeek-$postsLastWeek)/$postsLastWeek*100,1):($postsThisWeek>0?100:0);

    // Engagement growth
    $engThisWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $engLastWeek=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $engWoW=$engLastWeek>0?round(($engThisWeek-$engLastWeek)/$engLastWeek*100,1):($engThisWeek>0?100:0);

    // Weekly DAU trend (4 weeks)
    $dauWeekly=[];
    for($w=3;$w>=0;$w--){
        $start=date('Y-m-d',strtotime("-".($w*7+6)." days"));
        $end=date('Y-m-d',strtotime("-".($w*7)." days"));
        $dau=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at BETWEEN ? AND ?",[$start,$end.' 23:59:59'])['c']);
        $dauWeekly[]=['week'=>'W-'.$w,'dau_avg'=>$dau,'start'=>$start];
    }

    return ['users'=>['this_week'=>$usersThisWeek,'last_week'=>$usersLastWeek,'wow'=>$userWoW],'posts'=>['this_week'=>$postsThisWeek,'last_week'=>$postsLastWeek,'wow'=>$postWoW],'engagement'=>['this_week'=>$engThisWeek,'last_week'=>$engLastWeek,'wow'=>$engWoW],'dau_weekly'=>$dauWeekly];
}, 600);

gm2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
