<?php
// ShipperShop API v2 — Post Audience Insights
// Who engages with your posts: follower/non-follower, top engagers, demographics
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

$d=db();

function ai2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

$data=cache_remember('audience_'.$uid, function() use($d,$uid) {
    // Top engagers (who likes/comments my posts most)
    $topLikers=$d->fetchAll("SELECT pl.user_id,u.fullname,u.avatar,u.shipping_company,COUNT(*) as likes FROM post_likes pl JOIN posts p ON pl.post_id=p.id JOIN users u ON pl.user_id=u.id WHERE p.user_id=? AND pl.user_id!=? GROUP BY pl.user_id ORDER BY likes DESC LIMIT 10",[$uid,$uid]);

    $topCommenters=$d->fetchAll("SELECT c.user_id,u.fullname,u.avatar,COUNT(*) as comments FROM comments c JOIN posts p ON c.post_id=p.id JOIN users u ON c.user_id=u.id WHERE p.user_id=? AND c.user_id!=? GROUP BY c.user_id ORDER BY comments DESC LIMIT 10",[$uid,$uid]);

    // Follower vs non-follower engagement
    $totalLikers=intval($d->fetchOne("SELECT COUNT(DISTINCT pl.user_id) as c FROM post_likes pl JOIN posts p ON pl.post_id=p.id WHERE p.user_id=? AND pl.user_id!=?",[$uid,$uid])['c']);
    $followerLikers=intval($d->fetchOne("SELECT COUNT(DISTINCT pl.user_id) as c FROM post_likes pl JOIN posts p ON pl.post_id=p.id JOIN follows f ON pl.user_id=f.follower_id AND f.following_id=? WHERE p.user_id=? AND pl.user_id!=?",[$uid,$uid,$uid])['c']);
    $nonFollowerLikers=$totalLikers-$followerLikers;

    // Company breakdown of engagers
    $byCompany=$d->fetchAll("SELECT u.shipping_company as company, COUNT(DISTINCT pl.user_id) as users FROM post_likes pl JOIN posts p ON pl.post_id=p.id JOIN users u ON pl.user_id=u.id WHERE p.user_id=? AND u.shipping_company IS NOT NULL AND u.shipping_company!='' GROUP BY u.shipping_company ORDER BY users DESC LIMIT 8",[$uid]);

    return ['top_likers'=>$topLikers,'top_commenters'=>$topCommenters,'follower_ratio'=>['followers'=>$followerLikers,'non_followers'=>$nonFollowerLikers,'total'=>$totalLikers],'by_company'=>$byCompany];
}, 600);

ai2_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
