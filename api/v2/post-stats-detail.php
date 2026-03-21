<?php
// ShipperShop API v2 — Detailed Post Statistics
// Comprehensive analytics for a single post
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function pd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$postId=intval($_GET['post_id']??0);
if(!$postId) pd_ok('Missing post_id');

$uid=optional_auth();
$post=$d->fetchOne("SELECT p.*,u.fullname,u.avatar,u.shipping_company FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$postId]);
if(!$post) pd_ok('Post not found');

// Only owner can see detailed stats
$isOwner=$uid&&intval($post['user_id'])===$uid;

$data=['post_id'=>$postId,'likes'=>intval($post['likes_count']),'comments'=>intval($post['comments_count']),'views'=>intval($post['view_count']??$post['views']??0),'shares'=>intval($post['shares_count']??0),'created_at'=>$post['created_at']];

if($isOwner){
    // Engagement rate
    $views=max(1,intval($data['views']));
    $data['engagement_rate']=round(($data['likes']+$data['comments']+$data['shares'])/$views*100,2);

    // Likes over time (daily for last 7 days)
    $data['likes_timeline']=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM post_likes WHERE post_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY day",[$postId]);

    // Top commenters
    $data['top_commenters']=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,COUNT(c.id) as comment_count FROM comments c JOIN users u ON c.user_id=u.id WHERE c.post_id=? GROUP BY c.user_id ORDER BY comment_count DESC LIMIT 5",[$postId]);

    // Read time estimate (Vietnamese ~200 words/min)
    $wordCount=mb_substr_count($post['content']??'',' ')+1;
    $data['read_time_seconds']=max(5,round($wordCount/200*60));

    // Share stats
    $shareKey='share_stats_'.$postId;
    $shareRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$shareKey]);
    if($shareRow){$shareData=json_decode($shareRow['value'],true);$data['share_platforms']=$shareData['platforms']??[];}
}

pd_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
