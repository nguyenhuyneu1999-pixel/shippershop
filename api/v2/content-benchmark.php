<?php
// ShipperShop API v2 — Content Benchmark
// Compare user's content performance against platform averages and top performers
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

function cb3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$days=min(intval($_GET['days']??30),90);

$data=cache_remember('benchmark_'.$uid.'_'.$days, function() use($d,$uid,$days) {
    // User stats
    $userPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['c']);
    $userLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['s']);
    $userComments=intval($d->fetchOne("SELECT COALESCE(SUM(comments_count),0) as s FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)",[$uid])['s']);
    $userEngRate=$userPosts>0?round(($userLikes+$userComments)/$userPosts,1):0;

    // Platform averages
    $activeUsers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['c']);
    $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['s']);
    $totalComments=intval($d->fetchOne("SELECT COALESCE(SUM(comments_count),0) as s FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)")['s']);
    $avgPostsPerUser=$activeUsers>0?round($totalPosts/$activeUsers,1):0;
    $avgLikesPerPost=$totalPosts>0?round($totalLikes/$totalPosts,1):0;
    $avgEngRate=$totalPosts>0?round(($totalLikes+$totalComments)/$totalPosts,1):0;

    // Top 10% threshold
    $top10=$d->fetchOne("SELECT AVG(t.eng) as avg_eng FROM (SELECT user_id, SUM(likes_count+comments_count)/COUNT(*) as eng FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY user_id ORDER BY eng DESC LIMIT ".max(1,intval($activeUsers*0.1)).") t");

    // Rank
    $rank=$d->fetchOne("SELECT COUNT(*) as c FROM (SELECT user_id, SUM(likes_count+comments_count)/COUNT(*) as eng FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) GROUP BY user_id HAVING eng > ?) t",[$userEngRate]);
    $userRank=intval($rank['c'])+1;
    $percentile=$activeUsers>0?round(100-($userRank/$activeUsers*100)):0;

    $metrics=[
        ['name'=>'Bai/'.($days<=7?'tuan':'thang'),'user'=>$userPosts,'avg'=>$avgPostsPerUser,'top10'=>round($avgPostsPerUser*2.5),'unit'=>'bai'],
        ['name'=>'Likes TB/bai','user'=>$userPosts>0?round($userLikes/$userPosts,1):0,'avg'=>$avgLikesPerPost,'top10'=>round(floatval($top10['avg_eng']??0)*0.6,1),'unit'=>''],
        ['name'=>'Engagement/bai','user'=>$userEngRate,'avg'=>$avgEngRate,'top10'=>round(floatval($top10['avg_eng']??0),1),'unit'=>''],
    ];

    return ['metrics'=>$metrics,'rank'=>$userRank,'total_users'=>$activeUsers,'percentile'=>$percentile,'period_days'=>$days];
}, 600);

cb3_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
