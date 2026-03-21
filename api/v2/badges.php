<?php
// ShipperShop API v2 — User Badges
// Display badges, subscription badge, earned achievements
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

function bd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$BADGE_DEFS=[
    ['id'=>'pro','name'=>'⭐ PRO','color'=>'#f59e0b','desc'=>'Gói Shipper Pro'],
    ['id'=>'vip','name'=>'👑 VIP','color'=>'#8b5cf6','desc'=>'Gói Shipper VIP'],
    ['id'=>'premium','name'=>'💎 PREMIUM','color'=>'#ec4899','desc'=>'Gói Shipper Premium'],
    ['id'=>'verified','name'=>'✓ Xác minh','color'=>'#3b82f6','desc'=>'Tài khoản đã xác minh'],
    ['id'=>'top_poster','name'=>'🔥 Top Poster','color'=>'#ef4444','desc'=>'Top 10 bài viết tuần'],
    ['id'=>'helpful','name'=>'💡 Hữu ích','color'=>'#22c55e','desc'=>'100+ ghi chú được thích'],
    ['id'=>'veteran','name'=>'🎖️ Kỳ cựu','color'=>'#6366f1','desc'=>'Thành viên 6+ tháng'],
    ['id'=>'streak_7','name'=>'🔥7','color'=>'#f97316','desc'=>'Streak 7 ngày liên tiếp'],
    ['id'=>'streak_30','name'=>'🔥30','color'=>'#dc2626','desc'=>'Streak 30 ngày liên tiếp'],
    ['id'=>'first_post','name'=>'📝','color'=>'#64748b','desc'=>'Bài viết đầu tiên'],
    ['id'=>'100_likes','name'=>'❤️100','color'=>'#e11d48','desc'=>'100 thành công'],
    ['id'=>'social','name'=>'👥 Kết nối','color'=>'#0ea5e9','desc'=>'50+ người theo dõi'],
];

try {

// Get all badges for a user
if(!$action||$action==='user'){
    $tid=intval($_GET['user_id']??0);
    if(!$tid){$uid=optional_auth();$tid=$uid;}
    if(!$tid) bd_ok('OK',[]);

    $result=cache_remember('user_badges_'.$tid, function() use($d,$tid,$BADGE_DEFS) {
        $badges=[];
        $user=$d->fetchOne("SELECT is_verified,created_at FROM users WHERE id=?",[$tid]);

        // Subscription badge
        $sub=$d->fetchOne("SELECT sp.name,sp.id as plan_id FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.end_date>NOW() ORDER BY sp.id DESC LIMIT 1",[$tid]);
        if($sub){
            $planId=intval($sub['plan_id']);
            if($planId>=4) $badges[]='premium';
            elseif($planId>=3) $badges[]='vip';
            elseif($planId>=2) $badges[]='pro';
        }

        // Verified
        if($user&&intval($user['is_verified'])) $badges[]='verified';

        // Veteran (6+ months)
        if($user&&$user['created_at']&&(time()-strtotime($user['created_at']))>180*86400) $badges[]='veteran';

        // Earned badges from user_badges table
        $earned=$d->fetchAll("SELECT badge_id FROM user_badges WHERE user_id=?",[$tid]);
        foreach($earned as $e) $badges[]=$e['badge_id'];

        // Streak badges
        $streak=$d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$tid]);
        if($streak){
            if(intval($streak['current_streak'])>=30) $badges[]='streak_30';
            elseif(intval($streak['current_streak'])>=7) $badges[]='streak_7';
        }

        $badges=array_unique($badges);

        // Map to full badge objects
        $badgeMap=[];
        foreach($BADGE_DEFS as $b) $badgeMap[$b['id']]=$b;
        $result=[];
        foreach($badges as $bid){
            if(isset($badgeMap[$bid])) $result[]=$badgeMap[$bid];
        }
        return $result;
    }, 300);

    bd_ok('OK',$result);
}

// All available badges
if($action==='all'){
    bd_ok('OK',$BADGE_DEFS);
}

// Badge leaderboard (who has the most badges)
if($action==='leaderboard'){
    $leaders=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.is_verified,COUNT(ub.badge_id) as badge_count FROM user_badges ub JOIN users u ON ub.user_id=u.id WHERE u.`status`='active' GROUP BY u.id ORDER BY badge_count DESC LIMIT 20");
    bd_ok('OK',$leaders);
}

bd_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
