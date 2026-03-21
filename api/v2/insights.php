<?php
// ShipperShop API v2 — User Content Insights
// Weekly summary: best post, engagement trends, follower growth, tips
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

$d=db();$action=$_GET['action']??'';

function in_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Weekly summary
if(!$action||$action==='weekly'){
    $data=cache_remember('insights_weekly_'.$uid, function() use($d,$uid) {
        // This week
        $thisWeek=$d->fetchOne("SELECT COUNT(*) as posts,COALESCE(SUM(likes_count),0) as likes,COALESCE(SUM(comments_count),0) as comments,COALESCE(SUM(shares_count),0) as shares FROM posts WHERE user_id=? AND `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",[$uid]);
        // Last week
        $lastWeek=$d->fetchOne("SELECT COUNT(*) as posts,COALESCE(SUM(likes_count),0) as likes,COALESCE(SUM(comments_count),0) as comments FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN DATE_SUB(NOW(),INTERVAL 14 DAY) AND DATE_SUB(NOW(),INTERVAL 7 DAY)",[$uid]);

        $twPosts=intval($thisWeek['posts']);$lwPosts=intval($lastWeek['posts']);
        $twLikes=intval($thisWeek['likes']);$lwLikes=intval($lastWeek['likes']);

        // Follower change
        $newFollowers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)",[$uid])['c']);
        $totalFollowers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);

        // Best post this week
        $bestPost=$d->fetchOne("SELECT id,content,likes_count,comments_count,shares_count FROM posts WHERE user_id=? AND `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY) ORDER BY (likes_count*3+comments_count*5) DESC LIMIT 1",[$uid]);

        // Top commenting hour
        $bestHour=$d->fetchOne("SELECT HOUR(created_at) as h,AVG(likes_count) as avg_likes FROM posts WHERE user_id=? AND `status`='active' AND created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY) GROUP BY HOUR(created_at) ORDER BY avg_likes DESC LIMIT 1",[$uid]);

        // Tips
        $tips=[];
        if($twPosts===0) $tips[]=['icon'=>'📝','text'=>'Bạn chưa đăng bài tuần này. Hãy chia sẻ trải nghiệm!'];
        if($twPosts>0&&$twLikes>0&&$twLikes/$twPosts<2) $tips[]=['icon'=>'💡','text'=>'Thử thêm ảnh vào bài viết để tăng tương tác'];
        if($newFollowers>5) $tips[]=['icon'=>'🔥','text'=>'Tuyệt vời! Bạn có '.$newFollowers.' người theo dõi mới tuần này'];
        if($bestHour) $tips[]=['icon'=>'⏰','text'=>'Giờ vàng: đăng bài lúc '.intval($bestHour['h']).':00 được nhiều tương tác nhất'];
        if($totalFollowers>0&&$twPosts>0) $tips[]=['icon'=>'📊','text'=>'Tỷ lệ tiếp cận: ~'.round($twLikes/$totalFollowers*100).'% người theo dõi tương tác'];

        return [
            'this_week'=>['posts'=>$twPosts,'likes'=>$twLikes,'comments'=>intval($thisWeek['comments']),'shares'=>intval($thisWeek['shares'])],
            'last_week'=>['posts'=>$lwPosts,'likes'=>$lwLikes,'comments'=>intval($lastWeek['comments'])],
            'changes'=>['posts'=>$twPosts-$lwPosts,'likes'=>$twLikes-$lwLikes],
            'followers'=>['total'=>$totalFollowers,'new'=>$newFollowers],
            'best_post'=>$bestPost,
            'best_hour'=>$bestHour?intval($bestHour['h']):null,
            'tips'=>$tips,
        ];
    }, 1800);

    in_ok('OK',$data);
}

// Engagement rate over time
if($action==='engagement_trend'){
    $weeks=min(intval($_GET['weeks']??8),52);
    $trend=[];
    for($i=$weeks-1;$i>=0;$i--){
        $start=date('Y-m-d',strtotime("-".($i*7+7)." days"));
        $end=date('Y-m-d',strtotime("-".($i*7)." days"));
        $row=$d->fetchOne("SELECT COUNT(*) as posts,COALESCE(SUM(likes_count),0) as likes,COALESCE(SUM(comments_count),0) as comments FROM posts WHERE user_id=? AND `status`='active' AND created_at BETWEEN ? AND ?",[$uid,$start,$end]);
        $trend[]=['week_start'=>$start,'posts'=>intval($row['posts']),'likes'=>intval($row['likes']),'comments'=>intval($row['comments'])];
    }
    in_ok('OK',$trend);
}

in_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
