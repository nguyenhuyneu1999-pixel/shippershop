<?php
// ShipperShop API v2 — Post Analytics
// Per-post stats: views, engagement rate, reach, hourly breakdown
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

$d=db();$action=$_GET['action']??'';

function pa_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pa_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// Single post analytics
if(!$action||$action==='post'){
    $pid=intval($_GET['post_id']??0);
    if(!$pid) pa_fail('Missing post_id');
    $post=$d->fetchOne("SELECT user_id,likes_count,comments_count,shares_count,created_at FROM posts WHERE id=?",[$pid]);
    if(!$post) pa_fail('Post not found',404);
    if(intval($post['user_id'])!==$uid){
        $role=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$role||$role['role']!=='admin') pa_fail('Only post author or admin',403);
    }

    // Views from analytics_views
    $views=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views WHERE page='post' AND item_id=?",[$pid])['c']??0);
    // Unique viewers
    $uniqueViews=intval($d->fetchOne("SELECT COUNT(DISTINCT ip) as c FROM analytics_views WHERE page='post' AND item_id=?",[$pid])['c']??0);
    // Reactions breakdown
    $reactions=$d->fetchAll("SELECT reaction,COUNT(*) as count FROM post_reactions WHERE post_id=? GROUP BY reaction",[$pid]);
    $reactionMap=[];foreach($reactions as $r) $reactionMap[$r['reaction']]=intval($r['count']);
    // Engagement rate
    $engagement=$views>0?round(($post['likes_count']+$post['comments_count']+$post['shares_count'])/$views*100,1):0;
    // Saves
    $saves=intval($d->fetchOne("SELECT COUNT(*) as c FROM saved_posts WHERE post_id=?",[$pid])['c']);
    // Age
    $ageHours=round((time()-strtotime($post['created_at']))/3600,1);

    pa_ok('OK',[
        'post_id'=>$pid,
        'views'=>$views,
        'unique_views'=>$uniqueViews,
        'likes'=>intval($post['likes_count']),
        'comments'=>intval($post['comments_count']),
        'shares'=>intval($post['shares_count']),
        'saves'=>$saves,
        'reactions'=>$reactionMap,
        'engagement_rate'=>$engagement,
        'age_hours'=>$ageHours,
        'created_at'=>$post['created_at'],
    ]);
}

// My posts overview (dashboard for content creators)
if($action==='overview'){
    $days=min(intval($_GET['days']??30),90);

    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$uid])['c']);
    $totalLikes=intval($d->fetchOne("SELECT SUM(likes_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$uid])['s']);
    $totalComments=intval($d->fetchOne("SELECT SUM(comments_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$uid])['s']);
    $totalShares=intval($d->fetchOne("SELECT SUM(shares_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$uid])['s']);
    $totalViews=intval($d->fetchOne("SELECT COUNT(*) as c FROM analytics_views av JOIN posts p ON av.item_id=p.id WHERE av.page='post' AND p.user_id=?",[$uid])['c']??0);

    // Per-day stats
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as posts,SUM(likes_count) as likes FROM posts WHERE user_id=? AND `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) GROUP BY DATE(created_at) ORDER BY day",[$uid]);

    // Top performing posts
    $topPosts=$d->fetchAll("SELECT id,content,likes_count,comments_count,shares_count,created_at FROM posts WHERE user_id=? AND `status`='active' ORDER BY (likes_count*3+comments_count*5+shares_count*2) DESC LIMIT 5",[$uid]);

    // Best posting time (hour with most engagement)
    $bestHour=$d->fetchOne("SELECT HOUR(created_at) as h,AVG(likes_count) as avg_likes FROM posts WHERE user_id=? AND `status`='active' GROUP BY HOUR(created_at) ORDER BY avg_likes DESC LIMIT 1",[$uid]);

    pa_ok('OK',[
        'total_posts'=>$totalPosts,
        'total_likes'=>$totalLikes,
        'total_comments'=>$totalComments,
        'total_shares'=>$totalShares,
        'total_views'=>$totalViews,
        'avg_likes'=>$totalPosts>0?round($totalLikes/$totalPosts,1):0,
        'avg_comments'=>$totalPosts>0?round($totalComments/$totalPosts,1):0,
        'overall_engagement'=>$totalViews>0?round(($totalLikes+$totalComments+$totalShares)/$totalViews*100,1):0,
        'daily'=>$daily,
        'top_posts'=>$topPosts,
        'best_posting_hour'=>$bestHour?intval($bestHour['h']):null,
        'period_days'=>$days,
    ]);
}

pa_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
