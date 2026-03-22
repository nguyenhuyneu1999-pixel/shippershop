<?php
// ShipperShop API v2 — User Engagement Score
// Measures how active/engaged a user is compared to platform average
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

function es_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) es_ok('OK',['score'=>0]);

$days=min(intval($_GET['days']??30),90);

$data=cache_remember('engagement_'.$userId.'_'.$days, function() use($d,$userId,$days) {
    // User metrics (last N days)
    $posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$userId])['c']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$userId])['c']);
    $likesGiven=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$userId])['c']);
    $likesReceived=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$userId])['s']);

    // Platform averages
    $totalActive=max(1,intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']));
    $avgPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c'])/$totalActive;
    $avgComments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c'])/$totalActive;

    // Score (0-100)
    $score=0;
    $score+=min(30,round($posts/max($avgPosts,1)*15)); // Post frequency
    $score+=min(25,round($comments/max($avgComments,1)*12.5)); // Comment activity
    $score+=min(20,round($likesGiven/10)); // Engagement giving
    $score+=min(15,round($likesReceived/max($posts,1)/3*15)); // Content quality
    $score+=min(10,$posts>0?10:0); // Active indicator
    $score=min(100,$score);

    // Percentile
    $rank='Trung bình';
    if($score>=80) $rank='Xuất sắc';
    elseif($score>=60) $rank='Tích cực';
    elseif($score>=40) $rank='Khá';
    elseif($score>=20) $rank='Trung bình';
    else $rank='Mới';

    return [
        'score'=>$score,
        'rank'=>$rank,
        'period_days'=>$days,
        'metrics'=>['posts'=>$posts,'comments'=>$comments,'likes_given'=>$likesGiven,'likes_received'=>$likesReceived],
        'platform_avg'=>['posts'=>round($avgPosts,1),'comments'=>round($avgComments,1),'active_users'=>$totalActive],
    ];
}, 300);

es_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
