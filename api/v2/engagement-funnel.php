<?php
// ShipperShop API v2 — Admin Engagement Funnel
// Track user engagement pipeline: view→like→comment→share→follow conversion rates
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

function ef2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ef2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ef2_fail('Admin only',403);

$days=min(intval($_GET['days']??30),90);

$data=cache_remember('engagement_funnel_'.$days, function() use($d,$days) {
    $viewers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM analytics_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $likers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM post_likes WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $commenters=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $sharers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE shares_count>0 AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(DISTINCT follower_id) as c FROM follows WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);

    $base=max(1,$viewers);
    $funnel=[
        ['stage'=>'View','count'=>$viewers,'pct'=>100,'icon'=>'👁','color'=>'#3b82f6'],
        ['stage'=>'Like','count'=>$likers,'pct'=>round($likers/$base*100,1),'icon'=>'❤️','color'=>'#ef4444'],
        ['stage'=>'Comment','count'=>$commenters,'pct'=>round($commenters/$base*100,1),'icon'=>'💬','color'=>'#f59e0b'],
        ['stage'=>'Share','count'=>$sharers,'pct'=>round($sharers/$base*100,1),'icon'=>'🔄','color'=>'#22c55e'],
        ['stage'=>'Follow','count'=>$followers,'pct'=>round($followers/$base*100,1),'icon'=>'👥','color'=>'#7c3aed'],
    ];

    // Step conversion rates
    for($i=1;$i<count($funnel);$i++){
        $prev=max(1,$funnel[$i-1]['count']);
        $funnel[$i]['step_rate']=round($funnel[$i]['count']/$prev*100,1);
    }
    $funnel[0]['step_rate']=100;

    $overallConversion=$viewers>0?round($followers/$viewers*100,2):0;

    return ['funnel'=>$funnel,'overall_conversion'=>$overallConversion,'period_days'=>$days];
}, 1800);

ef2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
