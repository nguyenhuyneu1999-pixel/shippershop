<?php
// ShipperShop API v2 — User Reputation Score
// Calculated from posts, likes, comments, deliveries, verification, streaks
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

function rp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){$uid=optional_auth();$userId=$uid;}
if(!$userId) rp_ok('OK',['score'=>0]);

$action=$_GET['action']??'';

// Calculate reputation
if(!$action||$action==='score'){
    $data=cache_remember('reputation_'.$userId, function() use($d,$userId) {
        $u=$d->fetchOne("SELECT total_posts,total_success,is_verified,created_at FROM users WHERE id=?",[$userId]);
        if(!$u) return ['score'=>0,'level'=>'Mới','icon'=>'🌱','tier'=>0,'progress'=>0,'factors'=>[],'next_level'=>null];

        $score=0;
        $factors=[];

        // Posts (max 200 points)
        $posts=min(intval($u['total_posts']),200);
        $score+=$posts;
        $factors[]=['name'=>'Bài viết','value'=>$posts,'max'=>200];

        // Deliveries (max 300 points)
        $deliveries=min(intval($u['total_success'])*2,300);
        $score+=$deliveries;
        $factors[]=['name'=>'Đơn giao','value'=>$deliveries,'max'=>300];

        // Likes received (max 150 points)
        $likesReceived=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=?",[$userId])['s']);
        $likePoints=min($likesReceived,150);
        $score+=$likePoints;
        $factors[]=['name'=>'Lượt thích nhận','value'=>$likePoints,'max'=>150];

        // Followers (max 100 points)
        $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
        $followerPoints=min($followers,100);
        $score+=$followerPoints;
        $factors[]=['name'=>'Người theo dõi','value'=>$followerPoints,'max'=>100];

        // Verification bonus
        if(intval($u['is_verified'])){$score+=50;$factors[]=['name'=>'Đã xác minh','value'=>50,'max'=>50];}

        // Account age bonus (max 100)
        $days=max(1,floor((time()-strtotime($u['created_at']))/86400));
        $agePoints=min(intval($days/3),100);
        $score+=$agePoints;
        $factors[]=['name'=>'Thâm niên','value'=>$agePoints,'max'=>100];

        // Streak bonus
        $streak=$d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$userId]);
        $streakPoints=min(intval($streak['current_streak']??0)*2,50);
        $score+=$streakPoints;
        $factors[]=['name'=>'Streak','value'=>$streakPoints,'max'=>50];

        // Level calculation
        $levels=[
            ['min'=>0,'name'=>'Mới bắt đầu','icon'=>'🌱','tier'=>1],
            ['min'=>50,'name'=>'Shipper tập sự','icon'=>'📦','tier'=>2],
            ['min'=>150,'name'=>'Shipper chuyên nghiệp','icon'=>'🚚','tier'=>3],
            ['min'=>300,'name'=>'Shipper kỳ cựu','icon'=>'⭐','tier'=>4],
            ['min'=>500,'name'=>'Shipper huyền thoại','icon'=>'🏆','tier'=>5],
            ['min'=>800,'name'=>'Shipper đại sư','icon'=>'👑','tier'=>6],
        ];
        $level=$levels[0];
        foreach($levels as $l){if($score>=$l['min'])$level=$l;}
        $nextLevel=null;
        foreach($levels as $l){if($l['min']>$score){$nextLevel=$l;break;}}

        return [
            'score'=>$score,
            'level'=>$level['name'],
            'icon'=>$level['icon'],
            'tier'=>$level['tier'],
            'next_level'=>$nextLevel,
            'progress'=>$nextLevel?round(($score-$level['min'])/($nextLevel['min']-$level['min'])*100):100,
            'factors'=>$factors,
        ];
    }, 300);

    rp_ok('OK',$data);
}

// Leaderboard by reputation
if($action==='leaderboard'){
    $limit=min(intval($_GET['limit']??20),50);
    // Approximate: sort by total_posts + total_success
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company,is_verified,total_posts,total_success,(total_posts + total_success*2) as rep_score FROM users WHERE `status`='active' ORDER BY rep_score DESC LIMIT $limit");
    rp_ok('OK',$users);
}

rp_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
