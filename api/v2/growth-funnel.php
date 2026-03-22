<?php
// ShipperShop API v2 — User Growth Funnel (Admin)
// Registration → First Post → Active → Subscriber conversion funnel
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

function gf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function gf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') gf_fail('Admin only',403);

$days=min(intval($_GET['days']??30),365);

$data=cache_remember('growth_funnel_'.$days, function() use($d,$days) {
    $registered=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $hasPost=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $active7d=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['c']);
    $hasFollower=intval($d->fetchOne("SELECT COUNT(DISTINCT following_id) as c FROM follows WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $subscriber=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE plan_id>=2 AND expires_at > NOW()")['c']);
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);

    $funnel=[
        ['stage'=>'Dang ky','count'=>$registered,'pct'=>100],
        ['stage'=>'Dang bai','count'=>$hasPost,'pct'=>$registered?round($hasPost/$registered*100,1):0],
        ['stage'=>'Active 7d','count'=>$active7d,'pct'=>$registered?round($active7d/$registered*100,1):0],
        ['stage'=>'Co follower','count'=>$hasFollower,'pct'=>$registered?round($hasFollower/$registered*100,1):0],
        ['stage'=>'Subscriber','count'=>$subscriber,'pct'=>$registered?round($subscriber/$registered*100,1):0],
    ];

    // Retention cohorts (simplified)
    $retention=[];
    for($w=1;$w<=4;$w++){
        $start=$w*7;$end=($w-1)*7;
        $cohort=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL $start DAY) AND DATE_SUB(NOW(), INTERVAL $end DAY)")['c']);
        $retention[]=['week'=>'Tuan '.$w,'active'=>$cohort];
    }

    return ['funnel'=>$funnel,'retention'=>$retention,'total_users'=>$totalUsers,'period_days'=>$days];
}, 600);

gf_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
