<?php
// ShipperShop API v2 — User Portfolio
// Public portfolio page: best posts, stats summary, achievements showcase
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

function up2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$userId=intval($_GET['user_id']??0);
if(!$userId) up2_ok('Missing user_id');

$data=cache_remember('portfolio_'.$userId, function() use($d,$userId) {
    $u=$d->fetchOne("SELECT id,fullname,avatar,bio,shipping_company,total_posts,total_success,is_verified,created_at FROM users WHERE id=? AND `status`='active'",[$userId]);
    if(!$u) return null;

    $days=max(1,floor((time()-strtotime($u['created_at']))/86400));
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $following=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$userId])['c']);
    $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$userId])['s']);
    $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$userId])['c']);
    $xp=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$userId])['s']);
    $badges=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_badges WHERE user_id=?",[$userId])['c']);

    // Best posts (top 5 by engagement)
    $bestPosts=$d->fetchAll("SELECT id,content,likes_count,comments_count,created_at FROM posts WHERE user_id=? AND `status`='active' ORDER BY (likes_count+comments_count) DESC LIMIT 5",[$userId]);

    // Recent activity summary
    $recentPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$userId])['c']);
    $recentComments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",[$userId])['c']);

    $level=max(1,floor($xp/100)+1);

    return [
        'user'=>['id'=>intval($u['id']),'fullname'=>$u['fullname'],'avatar'=>$u['avatar'],'bio'=>$u['bio'],'company'=>$u['shipping_company'],'verified'=>intval($u['is_verified']),'joined'=>$u['created_at'],'days_active'=>$days],
        'stats'=>['posts'=>intval($u['total_posts']),'deliveries'=>intval($u['total_success']),'likes'=>$totalLikes,'followers'=>$followers,'following'=>$following,'groups'=>$groups,'xp'=>$xp,'level'=>$level,'badges'=>$badges],
        'best_posts'=>$bestPosts,
        'recent_30d'=>['posts'=>$recentPosts,'comments'=>$recentComments],
    ];
}, 300);

if(!$data) up2_ok('User not found');
up2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
