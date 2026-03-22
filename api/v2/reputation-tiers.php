<?php
// ShipperShop API v2 — Reputation Tiers
// Detailed reputation system with tier progression and rewards
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

$TIERS=[
    ['id'=>'bronze','name'=>'Dong','icon'=>'🥉','min_score'=>0,'max_score'=>99,'color'=>'#cd7f32','perks'=>['3 bai/ngay','Badge Dong']],
    ['id'=>'silver','name'=>'Bac','icon'=>'🥈','min_score'=>100,'max_score'=>299,'color'=>'#c0c0c0','perks'=>['10 bai/ngay','Badge Bac','Uu tien hien thi']],
    ['id'=>'gold','name'=>'Vang','icon'=>'🥇','min_score'=>300,'max_score'=>599,'color'=>'#ffd700','perks'=>['20 bai/ngay','Badge Vang','Uu tien hien thi','Link rut gon']],
    ['id'=>'platinum','name'=>'Bach kim','icon'=>'💎','min_score'=>600,'max_score'=>999,'color'=>'#e5e4e2','perks'=>['Khong gioi han','Badge Bach kim','Uu tien cao','Analytics nang cao']],
    ['id'=>'diamond','name'=>'Kim cuong','icon'=>'👑','min_score'=>1000,'max_score'=>99999,'color'=>'#b9f2ff','perks'=>['Khong gioi han','Badge Kim cuong','Top uu tien','Moi tinh nang','Ho tro rieng']],
];

function rt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$action=$_GET['action']??'';

// List all tiers
if(!$action||$action==='tiers'){
    rt_ok('OK',['tiers'=>$TIERS]);
}

// User's tier + progression
if($action==='user'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) rt_ok('OK',['tier'=>$TIERS[0],'score'=>0]);

    $data=cache_remember('rep_tier_'.$userId, function() use($d,$userId,$TIERS) {
        // Calculate reputation score
        $posts=intval($d->fetchOne("SELECT total_posts FROM users WHERE id=?",[$userId])['total_posts']??0);
        $likes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$userId])['s']);
        $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$userId])['c']);
        $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
        $deliveries=intval($d->fetchOne("SELECT total_success FROM users WHERE id=?",[$userId])['total_success']??0);
        $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$userId])['s']);
        $badges=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_badges WHERE user_id=?",[$userId])['c']);

        // Score formula
        $score=$posts*2+$likes+$comments+$followers*5+floor($deliveries/10)+floor($xp/5)+$badges*10;

        // Find tier
        $currentTier=$TIERS[0];$nextTier=null;
        for($i=count($TIERS)-1;$i>=0;$i--){
            if($score>=$TIERS[$i]['min_score']){$currentTier=$TIERS[$i];if($i<count($TIERS)-1)$nextTier=$TIERS[$i+1];break;}
        }

        $progress=0;
        if($nextTier) $progress=round(($score-$currentTier['min_score'])/($nextTier['min_score']-$currentTier['min_score'])*100);

        return ['score'=>$score,'tier'=>$currentTier,'next_tier'=>$nextTier,'progress'=>min(100,$progress),'breakdown'=>['posts'=>$posts*2,'likes'=>$likes,'comments'=>$comments,'followers'=>$followers*5,'deliveries'=>floor($deliveries/10),'xp'=>floor($xp/5),'badges'=>$badges*10]];
    }, 300);

    rt_ok('OK',$data);
}

// Leaderboard by reputation
if($action==='leaderboard'){
    $top=cache_remember('rep_leaderboard', function() use($d) {
        return $d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.total_posts,u.total_success,(u.total_posts*2+COALESCE((SELECT SUM(likes_count) FROM posts WHERE user_id=u.id AND `status`='active'),0)+COALESCE((SELECT COUNT(*) FROM follows WHERE following_id=u.id),0)*5) as rep_score FROM users u WHERE u.`status`='active' ORDER BY rep_score DESC LIMIT 20");
    }, 600);
    rt_ok('OK',$top);
}

rt_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
