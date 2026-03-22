<?php
// ShipperShop API v2 — Enhanced Shipper Profile
// Complete shipper profile: stats, ratings, routes, badges, earnings summary
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$userId=intval($_GET['user_id']??0);
if(!$userId){echo json_encode(['success'=>false,'message'=>'Missing user_id']);exit;}

$data=cache_remember('shipper_profile_v2_'.$userId, function() use($d,$userId) {
    $u=$d->fetchOne("SELECT id,fullname,avatar,bio,shipping_company,total_posts,total_success,created_at FROM users WHERE id=? AND `status`='active'",[$userId]);
    if(!$u) return null;

    $posts30=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$userId])['c'];
    $likes=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$userId])['s'];
    $followers=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c'];
    $following=$d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$userId])['c'];
    $streak=$d->fetchOne("SELECT current_streak,longest_streak FROM user_streaks WHERE user_id=?",[$userId]);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$userId])['s']);

    // Areas
    $areas=$d->fetchAll("SELECT province,COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY c DESC LIMIT 5",[$userId]);

    // Activity days this month
    $activeDays=intval($d->fetchOne("SELECT COUNT(DISTINCT DATE(created_at)) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')",[$userId])['c']);

    $daysSinceJoin=max(1,floor((time()-strtotime($u['created_at']))/86400));
    $avgPostsDay=round(intval($u['total_posts'])/$daysSinceJoin,1);

    return ['user'=>$u,'stats'=>['posts_30d'=>intval($posts30),'total_likes'=>intval($likes),'followers'=>intval($followers),'following'=>intval($following),'streak'=>intval($streak['current_streak']??0),'longest_streak'=>intval($streak['longest_streak']??0),'xp'=>$xp,'level'=>max(1,floor($xp/100)+1),'active_days_month'=>$activeDays,'avg_posts_day'=>$avgPostsDay,'days_since_join'=>$daysSinceJoin],'areas'=>$areas];
}, 300);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
