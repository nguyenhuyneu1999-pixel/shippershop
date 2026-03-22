<?php
// ShipperShop API v2 — Post Analytics V2
// Deep analytics for a single post: engagement timeline, viewer demographics
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$postId=intval($_GET['post_id']??0);
if(!$postId){echo json_encode(['success'=>false,'message'=>'Missing post_id']);exit;}

$p=$d->fetchOne("SELECT id,content,likes_count,comments_count,shares_count,view_count,user_id,created_at FROM posts WHERE id=? AND `status`='active'",[$postId]);
if(!$p){echo json_encode(['success'=>true,'data'=>null]);exit;}

// Engagement rate
$engagement=intval($p['likes_count'])+intval($p['comments_count'])+intval($p['shares_count']);
$views=max(1,intval($p['view_count']));
$engRate=round($engagement/$views*100,1);

// Like timeline (by day)
$likeTimeline=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as c FROM post_likes WHERE post_id=? GROUP BY DATE(created_at) ORDER BY day",[$postId]);

// Top commenters
$topCommenters=$d->fetchAll("SELECT c.user_id,u.fullname,u.avatar,COUNT(*) as count FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=? GROUP BY c.user_id ORDER BY count DESC LIMIT 5",[$postId]);

// Time since post
$hoursSince=round((time()-strtotime($p['created_at']))/3600,1);
$likesPerHour=$hoursSince>0?round($engagement/$hoursSince,2):0;

echo json_encode(['success'=>true,'data'=>['post'=>$p,'engagement'=>$engagement,'engagement_rate'=>$engRate,'views'=>$views,'likes_per_hour'=>$likesPerHour,'hours_since_post'=>$hoursSince,'like_timeline'=>$likeTimeline,'top_commenters'=>$topCommenters]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
