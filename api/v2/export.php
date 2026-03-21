<?php
// ShipperShop API v2 — User Data Export (GDPR-like)
// Export all user data as JSON
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function ex_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;}
function ex_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if(!$action||$action==='full'){
    // Rate limit: 1 full export per hour
    rate_enforce('data_export',1,3600);
    $user=$d->fetchOne("SELECT * FROM users WHERE id=?",[$uid]);
    if(!$user) ex_fail('User not found',404);
    // Remove sensitive fields
    unset($user['password'],$user['pin_hash'],$user['two_factor_secret']);

    // Posts
    $posts=$d->fetchAll("SELECT id,content,type,images,video_url,province,district,ward,likes_count,comments_count,shares_count,is_pinned,scheduled_at,is_draft,created_at,edited_at FROM posts WHERE user_id=? AND `status`!='deleted' ORDER BY created_at DESC",[$uid]);

    // Comments
    $comments=$d->fetchAll("SELECT id,post_id,content,likes_count,created_at FROM comments WHERE user_id=? AND `status`='active' ORDER BY created_at DESC LIMIT 500",[$uid]);

    // Likes
    $likes=$d->fetchAll("SELECT post_id,created_at FROM likes WHERE user_id=? ORDER BY created_at DESC LIMIT 1000",[$uid]);

    // Saved posts
    $saved=$d->fetchAll("SELECT post_id,created_at FROM saved_posts WHERE user_id=? ORDER BY created_at DESC",[$uid]);

    // Following
    $following=$d->fetchAll("SELECT f.following_id,u.fullname,f.created_at FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=?",[$uid]);

    // Followers
    $followers=$d->fetchAll("SELECT f.follower_id,u.fullname,f.created_at FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=?",[$uid]);

    // Groups
    $groups=$d->fetchAll("SELECT gm.group_id,g.name,gm.role,gm.joined_at FROM group_members gm JOIN `groups` g ON gm.group_id=g.id WHERE gm.user_id=?",[$uid]);

    // Messages (count only for privacy)
    $msgCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=?",[$uid])['c']);

    // Wallet
    $wallet=$d->fetchOne("SELECT balance,created_at FROM wallets WHERE user_id=?",[$uid]);
    $transactions=$d->fetchAll("SELECT type,amount,description,created_at FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$uid]);

    // Subscription
    $sub=$d->fetchOne("SELECT us.*,sp.name as plan_name FROM user_subscriptions us LEFT JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? ORDER BY us.created_at DESC LIMIT 1",[$uid]);

    // XP
    $xp=$d->fetchAll("SELECT action,xp,detail,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$uid]);

    // Stories (active)
    $stories=$d->fetchAll("SELECT id,content,image_url,background,view_count,expires_at,created_at FROM stories WHERE user_id=? ORDER BY created_at DESC LIMIT 50",[$uid]);

    // Bookmark collections
    $collections=$d->fetchAll("SELECT id,name,icon,post_count,created_at FROM bookmark_collections WHERE user_id=?",[$uid]);

    // Login attempts
    $logins=$d->fetchAll("SELECT ip,success,created_at FROM login_attempts WHERE email=? ORDER BY created_at DESC LIMIT 20",[$user['email']]);

    // Audit log
    $audit=$d->fetchAll("SELECT action,detail,ip,created_at FROM audit_log WHERE user_id=? ORDER BY created_at DESC LIMIT 50",[$uid]);

    $export=[
        'export_date'=>date('Y-m-d H:i:s'),
        'user'=>$user,
        'posts'=>['count'=>count($posts),'items'=>$posts],
        'comments'=>['count'=>count($comments),'items'=>$comments],
        'likes'=>['count'=>count($likes),'items'=>$likes],
        'saved_posts'=>['count'=>count($saved),'items'=>$saved],
        'following'=>['count'=>count($following),'items'=>$following],
        'followers'=>['count'=>count($followers),'items'=>$followers],
        'groups'=>['count'=>count($groups),'items'=>$groups],
        'messages_sent'=>$msgCount,
        'wallet'=>$wallet,
        'transactions'=>['count'=>count($transactions),'items'=>$transactions],
        'subscription'=>$sub,
        'xp_history'=>['count'=>count($xp),'items'=>$xp],
        'stories'=>['count'=>count($stories),'items'=>$stories],
        'bookmark_collections'=>['count'=>count($collections),'items'=>$collections],
        'login_history'=>$logins,
        'audit_log'=>$audit,
    ];

    // Audit this export
    try{db()->query("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,'data_export','Full data export',?,NOW())",[$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    ex_ok('Data export complete',$export);
}

// Summary only (lighter)
if($action==='summary'){
    $user=$d->fetchOne("SELECT id,fullname,email,created_at FROM users WHERE id=?",[$uid]);
    $postCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$uid])['c']);
    $commentCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND `status`='active'",[$uid])['c']);
    $likeCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE user_id=?",[$uid])['c']);
    $groupCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$uid])['c']);
    $msgCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=?",[$uid])['c']);

    ex_ok('OK',['user'=>$user,'stats'=>['posts'=>$postCount,'comments'=>$commentCount,'likes'=>$likeCount,'groups'=>$groupCount,'messages'=>$msgCount]]);
}

ex_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
