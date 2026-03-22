<?php
// ShipperShop API v2 — Post Reach Estimator
// Estimate how many users a post will reach based on follower network
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$postId=intval($_GET['post_id']??0);
$userId=intval($_GET['user_id']??0);

if($postId){
    $post=$d->fetchOne("SELECT user_id,likes_count,comments_count,shares_count,view_count FROM posts WHERE id=? AND `status`='active'",[$postId]);
    if(!$post){echo json_encode(['success'=>true,'data'=>['reach'=>0]]);exit;}
    $userId=intval($post['user_id']);
}

if(!$userId){echo json_encode(['success'=>true,'data'=>['reach'=>0]]);exit;}

$data=cache_remember('post_reach_'.$userId, function() use($d,$userId) {
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$userId])['c']);
    $groups=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$userId])['c']);
    $groupMembers=intval($d->fetchOne("SELECT COALESCE(SUM(gm_count),0) as s FROM (SELECT COUNT(*) as gm_count FROM group_members WHERE group_id IN (SELECT group_id FROM group_members WHERE user_id=?) GROUP BY group_id) t",[$userId])['s']);
    $avgViews=floatval($d->fetchOne("SELECT AVG(view_count) as a FROM posts WHERE user_id=? AND `status`='active' AND view_count>0",[$userId])['a']??0);

    // Estimated reach = followers + group members (dedup ~70%) + organic
    $directReach=$followers;
    $groupReach=intval($groupMembers*0.3);
    $organicReach=intval($avgViews*0.5);
    $totalReach=$directReach+$groupReach+$organicReach;

    return ['direct_followers'=>$followers,'group_reach'=>$groupReach,'organic_estimate'=>$organicReach,'total_estimated_reach'=>$totalReach,'groups_in'=>$groups,'avg_views'=>round($avgViews,1)];
}, 600);

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
