<?php
// ShipperShop API v2 — Daily Digest
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

try {

$uid=optional_auth();
$digest=[];

// Trending posts (top by engagement, last 7 days)
$digest['trending']=$d->fetchAll("SELECT p.id,p.content,p.likes_count,p.comments_count,u.fullname,u.avatar FROM posts p JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY (p.likes_count+p.comments_count) DESC LIMIT 5");

// Platform stats today
$newPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND created_at >= CURDATE()")['c']);
$newUsers=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= CURDATE()")['c']);
$newComments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= CURDATE()")['c']);
$onlineNow=intval($d->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_online=1")['c']);
$digest['today']=['new_posts'=>$newPosts,'new_users'=>$newUsers,'new_comments'=>$newComments,'online_now'=>$onlineNow];

// User-specific
if($uid){
    $myPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND created_at >= CURDATE()",[$uid])['c']);
    $unread=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?) AND sender_id!=? AND is_read=0",[$uid,$uid])['c']);
    $digest['my_stats']=['posts_today'=>$myPosts,'unread_messages'=>$unread];
}

// Active groups
$digest['active_groups']=$d->fetchAll("SELECT g.id,g.name,COUNT(gp.id) as posts_count FROM `groups` g JOIN group_posts gp ON g.id=gp.group_id WHERE gp.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) GROUP BY g.id ORDER BY posts_count DESC LIMIT 5");

$digest['date']=date('Y-m-d');
$digest['greeting']=intval(date('H'))<12?'Chao buoi sang!':(intval(date('H'))<18?'Chao buoi chieu!':'Chao buoi toi!');

echo json_encode(['success'=>true,'message'=>'OK','data'=>$digest],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage().' at line '.$e->getLine()]);
}
