<?php
// ShipperShop API v2 — Platform Century Stats
// SESSION 100 COMMEMORATIVE — Complete platform statistics snapshot
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$data=cache_remember('platform_century', function() use($d) {
    // Users
    $totalUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE `status`='active'")['c']);
    $totalPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active'")['c']);
    $totalComments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments")['c']);
    $totalLikes=intval($d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE `status`='active'")['s']);
    $totalGroups=intval($d->fetchOne("SELECT COUNT(*) as c FROM groups")['c']);
    $totalGroupPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c']);
    $totalMessages=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages")['c']);
    $totalConversations=intval($d->fetchOne("SELECT COUNT(*) as c FROM conversations")['c']);
    $totalFollows=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows")['c']);

    // Platform
    $dbSize=floatval($d->fetchOne("SELECT ROUND(SUM(data_length+index_length)/1048576,1) as mb FROM information_schema.tables WHERE table_schema=DATABASE()")['mb']??0);
    $tableCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()")['c']);
    $apiCount=count(glob(__DIR__.'/*.php'));
    $jsComps=count(glob(__DIR__.'/../../js/components/*.js'));
    $jsPages=count(glob(__DIR__.'/../../js/pages/*.js'));

    // Revenue
    $totalRevenue=intval($d->fetchOne("SELECT COALESCE(SUM(amount),0) as s FROM wallet_transactions WHERE type='deposit' AND `status`='completed'")['s']);
    $subscribers=intval($d->fetchOne("SELECT COUNT(*) as c FROM user_subscriptions WHERE expires_at > NOW() AND plan_id>=2")['c']);

    // Top users
    $topPosters=$d->fetchAll("SELECT u.fullname,u.total_posts FROM users u WHERE u.`status`='active' ORDER BY u.total_posts DESC LIMIT 5");
    $topLiked=$d->fetchAll("SELECT u.fullname,SUM(p.likes_count) as total_likes FROM users u JOIN posts p ON p.user_id=u.id WHERE p.`status`='active' GROUP BY u.id ORDER BY total_likes DESC LIMIT 5");

    return [
        'session'=>100,
        'version'=>'v10.0.0',
        'community'=>['users'=>$totalUsers,'posts'=>$totalPosts,'comments'=>$totalComments,'likes'=>$totalLikes,'groups'=>$totalGroups,'group_posts'=>$totalGroupPosts,'messages'=>$totalMessages,'conversations'=>$totalConversations,'follows'=>$totalFollows],
        'platform'=>['db_mb'=>$dbSize,'tables'=>$tableCount,'apis'=>$apiCount,'js_components'=>$jsComps,'js_pages'=>$jsPages],
        'revenue'=>['total'=>$totalRevenue,'subscribers'=>$subscribers],
        'top_posters'=>$topPosters,
        'top_liked'=>$topLiked,
        'milestone'=>'100 development sessions',
        'generated_at'=>date('c')
    ];
}, 60);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
