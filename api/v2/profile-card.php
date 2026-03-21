<?php
// ShipperShop API v2 — Profile Card (lightweight user preview)
// For hover cards, @mention popups, user previews
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
$tid=intval($_GET['user_id']??0);
if(!$tid){echo json_encode(['success'=>false,'message'=>'Missing user_id']);exit;}

$uid=optional_auth();

$data=cache_remember('profile_card_'.$tid, function() use($d,$tid) {
    $user=$d->fetchOne("SELECT id,fullname,avatar,bio,shipping_company,is_verified,is_online,last_active,created_at FROM users WHERE id=? AND `status`='active'",[$tid]);
    if(!$user) return null;

    $postCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$tid])['c']);
    $followerCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$tid])['c']);
    $followingCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$tid])['c']);

    // Subscription badge
    $sub=$d->fetchOne("SELECT sp.id as plan_id,sp.name FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.end_date>NOW() LIMIT 1",[$tid]);

    // Recent posts (3)
    $recentPosts=$d->fetchAll("SELECT id,content,likes_count,created_at FROM posts WHERE user_id=? AND `status`='active' AND is_draft=0 ORDER BY created_at DESC LIMIT 3",[$tid]);

    $online=intval($user['is_online'])&&$user['last_active']&&(time()-strtotime($user['last_active']))<300;

    return [
        'id'=>intval($user['id']),
        'fullname'=>$user['fullname'],
        'avatar'=>$user['avatar'],
        'bio'=>$user['bio'],
        'shipping_company'=>$user['shipping_company'],
        'is_verified'=>intval($user['is_verified']),
        'is_online'=>$online,
        'last_active'=>$user['last_active'],
        'joined'=>$user['created_at'],
        'posts'=>$postCount,
        'followers'=>$followerCount,
        'following'=>$followingCount,
        'plan_id'=>$sub?intval($sub['plan_id']):1,
        'plan_name'=>$sub?$sub['name']:'Miễn phí',
        'recent_posts'=>$recentPosts,
    ];
}, 120);

if(!$data){echo json_encode(['success'=>false,'message'=>'User not found']);exit;}

// Add follow status if authenticated
if($uid&&$uid!==$tid){
    $following=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$tid]);
    $data['is_following']=!!$following;
    $mutual=$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$tid,$uid]);
    $data['is_mutual']=!!$following&&!!$mutual;
}

echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
