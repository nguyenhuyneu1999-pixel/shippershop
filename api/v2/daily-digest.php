<?php
// ShipperShop API v2 — Daily Digest
// Personalized daily summary: trending, your stats, suggestions
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

function dd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=optional_auth();

$data=cache_remember('daily_digest_'.($uid?:0), function() use($d,$uid) {
    $digest=[];

    // Trending posts (last 24h, fallback to last 7d if empty today)
    $trending=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) ORDER BY (p.likes_count+p.comments_count) DESC LIMIT 5");
    if(!$trending){
        $trending=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' ORDER BY (p.likes_count+p.comments_count) DESC LIMIT 5");
    }
    $digest['trending']=$trending;

    // Platform stats today
    $digest['today']=[
        'new_posts'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']),
        'new_users'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()")['c']),
        'new_comments'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= CURDATE()")['c']),
        'online_now'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']),
    ];

    // User-specific stats
    if($uid){
        $myPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND created_at >= CURDATE()",[$uid])['c']);
        $myLikes=intval($d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id IN (SELECT id FROM posts WHERE user_id=?) AND created_at >= CURDATE()",[$uid])['c']);
        $newFollowers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=? AND created_at >= CURDATE()",[$uid])['c']);
        $unread=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?) AND sender_id!=? AND is_read=0",[$uid,$uid])['c']);
        $digest['my_stats']=['posts_today'=>$myPosts,'likes_received_today'=>$myLikes,'new_followers'=>$newFollowers,'unread_messages'=>$unread];
    }

    // Active groups
    $digest['active_groups']=$d->fetchAll("SELECT g.id,g.name,g.avatar,COUNT(gp.id) as posts_today FROM `groups` g JOIN group_posts gp ON g.id=gp.group_id WHERE gp.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY g.id ORDER BY posts_today DESC LIMIT 5");

    // Tip of the day (random recent post)
    $tip=$d->fetchOne("SELECT p.id,p.content,u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' ORDER BY p.likes_count DESC LIMIT 1");
    $digest['tip_of_day']=$tip;

    $digest['date']=date('Y-m-d');
    $digest['greeting']=date('H')<12?'Chào buổi sáng!':(date('H')<18?'Chào buổi chiều!':'Chào buổi tối!');

    return $digest;
}, 300);

dd_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
