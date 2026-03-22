<?php
// ShipperShop API v2 — Content Insights
// Author-facing analytics: best posting times, top content, engagement trends
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

function ci_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$days=min(intval($_GET['days']??30),90);

$data=cache_remember('insights_'.$uid.'_'.$days, function() use($d,$uid,$days) {
    // Top performing posts
    $topPosts=$d->fetchAll("SELECT id,content,likes_count,comments_count,created_at,type FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) ORDER BY (likes_count+comments_count) DESC LIMIT 5",[$uid]);

    // Best posting hours (when posts get most engagement)
    $hourly=$d->fetchAll("SELECT HOUR(created_at) as hour,AVG(likes_count) as avg_likes,AVG(comments_count) as avg_comments,COUNT(*) as post_count FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY HOUR(created_at) ORDER BY (AVG(likes_count)+AVG(comments_count)) DESC",[$uid]);

    // Best days of week
    $daily=$d->fetchAll("SELECT DAYOFWEEK(created_at) as dow,AVG(likes_count) as avg_likes,COUNT(*) as post_count FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DAYOFWEEK(created_at) ORDER BY avg_likes DESC",[$uid]);
    $dowNames=['','CN','T2','T3','T4','T5','T6','T7'];
    foreach($daily as &$dd){$dd['day_name']=$dowNames[intval($dd['dow'])]??'';}unset($dd);

    // Engagement trend (daily)
    $trend=$d->fetchAll("SELECT DATE(created_at) as day,SUM(likes_count) as likes,SUM(comments_count) as comments,COUNT(*) as posts FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day",[$uid]);

    // Content type breakdown
    $typeBreakdown=$d->fetchAll("SELECT COALESCE(type,'post') as type,COUNT(*) as count,AVG(likes_count) as avg_likes FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY type ORDER BY count DESC",[$uid]);

    // Totals
    $totals=$d->fetchOne("SELECT COUNT(*) as posts,COALESCE(SUM(likes_count),0) as likes,COALESCE(SUM(comments_count),0) as comments FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid]);

    // Best hour recommendation
    $bestHour=!empty($hourly)?intval($hourly[0]['hour']):null;

    return [
        'period_days'=>$days,
        'totals'=>['posts'=>intval($totals['posts']),'likes'=>intval($totals['likes']),'comments'=>intval($totals['comments'])],
        'top_posts'=>$topPosts,
        'best_hours'=>array_slice($hourly,0,5),
        'best_days'=>$daily,
        'daily_trend'=>$trend,
        'type_breakdown'=>$typeBreakdown,
        'recommendation'=>$bestHour!==null?'Đăng bài lúc '.str_pad($bestHour,2,'0',STR_PAD_LEFT).':00 để có tương tác cao nhất':null,
    ];
}, 300);

ci_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
