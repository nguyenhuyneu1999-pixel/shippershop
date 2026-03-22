<?php
// ShipperShop API v2 — Shipper Passport
// Complete shipper identity card: stats, badges, ratings, history, QR verify
// SESSION 100 SPECIAL — The Century Feature
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

function sp2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$targetId=intval($_GET['user_id']??$uid);

$data=cache_remember('shipper_passport_'.$targetId, function() use($d,$targetId) {
    $user=$d->fetchOne("SELECT id,fullname,avatar,email,shipping_company,total_posts,created_at FROM users WHERE id=? AND `status`='active'",[$targetId]);
    if(!$user) return null;

    // Basic stats
    $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$targetId])['s']);
    $totalComments=intval($d->fetchOne("SELECT COALESCE(SUM(comments_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$targetId])['s']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$targetId])['c']);
    $following=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$targetId])['c']);

    // Activity
    $daysActive=intval($d->fetchOne("SELECT COUNT(DISTINCT DATE(created_at)) as c FROM posts WHERE user_id=? AND `status`='active'",[$targetId])['c']);
    $memberDays=max(1,intval((time()-strtotime($user['created_at']))/86400));

    // Streak
    $streak=$d->fetchOne("SELECT current_streak,longest_streak FROM user_streaks WHERE user_id=?",[$targetId]);

    // XP + badges
    $xp=$d->fetchOne("SELECT xp,level FROM user_xp WHERE user_id=?",[$targetId]);
    $badges=$d->fetchAll("SELECT badge_id,earned_at FROM user_badges WHERE user_id=? ORDER BY earned_at DESC LIMIT 10",[$targetId]);

    // Subscription
    $sub=$d->fetchOne("SELECT sp.name as plan_name,us.expires_at FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.expires_at > NOW() ORDER BY us.expires_at DESC LIMIT 1",[$targetId]);

    // Reputation score
    $engRate=intval($user['total_posts'])>0?round(($totalLikes+$totalComments)/intval($user['total_posts']),1):0;
    $repScore=min(100,round(intval($user['total_posts'])*0.5+$followers*2+$totalLikes*0.3+$daysActive*0.8+intval($streak['current_streak']??0)*3));
    $repTier=$repScore>=80?'Gold':($repScore>=50?'Silver':($repScore>=20?'Bronze':'Starter'));
    $tierIcons=['Gold'=>'🥇','Silver'=>'🥈','Bronze'=>'🥉','Starter'=>'🆕'];

    // Passport ID
    $passportId='SS-'.strtoupper(substr(md5('passport_'.$targetId),0,8));

    return [
        'passport_id'=>$passportId,
        'user'=>['id'=>intval($user['id']),'fullname'=>$user['fullname'],'avatar'=>$user['avatar'],'company'=>$user['shipping_company'],'joined'=>$user['created_at'],'member_days'=>$memberDays],
        'stats'=>['posts'=>intval($user['total_posts']),'likes'=>$totalLikes,'comments'=>$totalComments,'followers'=>$followers,'following'=>$following,'days_active'=>$daysActive,'engagement_rate'=>$engRate],
        'streak'=>['current'=>intval($streak['current_streak']??0),'longest'=>intval($streak['longest_streak']??0)],
        'xp'=>['xp'=>intval($xp['xp']??0),'level'=>intval($xp['level']??1)],
        'badges'=>$badges,
        'subscription'=>$sub?['plan'=>$sub['plan_name'],'expires'=>$sub['expires_at']]:null,
        'reputation'=>['score'=>$repScore,'tier'=>$repTier,'icon'=>$tierIcons[$repTier]??'🆕'],
    ];
}, 300);

if(!$data) sp2_ok('User not found');
sp2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
