<?php
// ShipperShop API v2 — User Reputation Score
// Composite score: posts, likes received, comments, streak, verification, age
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

function rp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Get reputation for a user
if(!$action||$action==='score'){
    $tid=intval($_GET['user_id']??0);
    if(!$tid){$uid=optional_auth();$tid=$uid;}
    if(!$tid) rp_ok('OK',null);

    $data=cache_remember('reputation_'.$tid, function() use($d,$tid) {
        $user=$d->fetchOne("SELECT created_at,is_verified FROM users WHERE id=?",[$tid]);
        if(!$user) return null;

        $posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$tid])['c']);
        $likesReceived=intval($d->fetchOne("SELECT SUM(likes_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$tid])['s']);
        $commentsReceived=intval($d->fetchOne("SELECT SUM(comments_count) as s FROM posts WHERE user_id=? AND `status`='active'",[$tid])['s']);
        $commentsGiven=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND `status`='active'",[$tid])['c']);
        $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$tid])['c']);
        $streak=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$tid])['current_streak']??0);
        $xp=intval($d->fetchOne("SELECT SUM(xp) as s FROM user_xp WHERE user_id=?",[$tid])['s']);
        $ageDays=max(1,round((time()-strtotime($user['created_at']))/86400));
        $verified=intval($user['is_verified']);

        // Score formula
        $score=0;
        $score+=min($posts*5, 500);              // Max 500 from posts
        $score+=min($likesReceived*2, 1000);      // Max 1000 from likes
        $score+=min($commentsReceived, 300);      // Max 300 from comments received
        $score+=min($commentsGiven*2, 200);       // Max 200 from engagement
        $score+=min($followers*3, 600);           // Max 600 from followers
        $score+=min($streak*10, 300);             // Max 300 from streak
        $score+=min(floor($xp/10), 200);          // Max 200 from XP
        $score+=min(floor($ageDays/30)*20, 200);  // Max 200 from age
        $score+=$verified?300:0;                  // 300 for verified

        $maxScore=3600;
        $pct=round($score/$maxScore*100);

        // Level based on score
        $levels=[0=>'Tân binh',200=>'Shipper',500=>'Shipper tích cực',1000=>'Shipper uy tín',2000=>'Shipper chuyên nghiệp',3000=>'Shipper huyền thoại'];
        $level='Tân binh';
        foreach($levels as $threshold=>$name){if($score>=$threshold) $level=$name;}

        return [
            'score'=>$score,'max'=>$maxScore,'pct'=>$pct,'level'=>$level,
            'breakdown'=>[
                'posts'=>['value'=>$posts,'points'=>min($posts*5,500),'max'=>500],
                'likes_received'=>['value'=>$likesReceived,'points'=>min($likesReceived*2,1000),'max'=>1000],
                'comments_received'=>['value'=>$commentsReceived,'points'=>min($commentsReceived,300),'max'=>300],
                'engagement'=>['value'=>$commentsGiven,'points'=>min($commentsGiven*2,200),'max'=>200],
                'followers'=>['value'=>$followers,'points'=>min($followers*3,600),'max'=>600],
                'streak'=>['value'=>$streak,'points'=>min($streak*10,300),'max'=>300],
                'xp'=>['value'=>$xp,'points'=>min(floor($xp/10),200),'max'=>200],
                'age_days'=>['value'=>$ageDays,'points'=>min(floor($ageDays/30)*20,200),'max'=>200],
                'verified'=>['value'=>$verified,'points'=>$verified?300:0,'max'=>300],
            ],
        ];
    }, 600);

    rp_ok('OK',$data);
}

// Reputation leaderboard
if($action==='leaderboard'){
    $limit=min(intval($_GET['limit']??20),50);
    // Simple leaderboard based on total engagement
    $leaders=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_verified,
        (SELECT SUM(likes_count)+SUM(comments_count) FROM posts WHERE user_id=u.id AND `status`='active') as engagement,
        (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as followers
        FROM users u WHERE u.`status`='active'
        ORDER BY engagement DESC LIMIT $limit");
    rp_ok('OK',$leaders);
}

rp_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
