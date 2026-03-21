<?php
// ShipperShop API v2 — User Data Export (GDPR-style)
// Export user's own data as JSON
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

function ex_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ex_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
rate_enforce('data_export',3,86400); // 3 per day

if(!$action||$action==='full'){
    // Profile
    $profile=$d->fetchOne("SELECT id,fullname,email,phone,bio,avatar,shipping_company,is_verified,created_at FROM users WHERE id=?",[$uid]);

    // Posts
    $posts=$d->fetchAll("SELECT id,content,type,province,district,likes_count,comments_count,shares_count,created_at FROM posts WHERE user_id=? AND `status`='active' ORDER BY created_at DESC",[$uid]);

    // Comments
    $comments=$d->fetchAll("SELECT c.id,c.post_id,c.content,c.created_at FROM comments c WHERE c.user_id=? ORDER BY c.created_at DESC LIMIT 500",[$uid]);

    // Likes
    $likes=$d->fetchAll("SELECT post_id,created_at FROM likes WHERE user_id=? ORDER BY created_at DESC LIMIT 1000",[$uid]);

    // Follows
    $following=$d->fetchAll("SELECT u.id,u.fullname FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=?",[$uid]);
    $followers=$d->fetchAll("SELECT u.id,u.fullname FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=?",[$uid]);

    // Messages (own messages only)
    $messages=$d->fetchAll("SELECT m.id,m.conversation_id,m.content,m.created_at FROM messages m WHERE m.sender_id=? ORDER BY m.created_at DESC LIMIT 500",[$uid]);

    // Wallet
    $wallet=$d->fetchOne("SELECT balance FROM wallets WHERE user_id=?",[$uid]);
    $transactions=$d->fetchAll("SELECT type,amount,description,created_at FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$uid]);

    // Saved posts
    $saved=$d->fetchAll("SELECT post_id,created_at FROM saved_posts WHERE user_id=?",[$uid]);

    // XP + badges
    $xp=$d->fetchAll("SELECT action,xp,detail,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$uid]);
    $badges=$d->fetchAll("SELECT badge_id,created_at FROM user_badges WHERE user_id=?",[$uid]);

    // Groups
    $groups=$d->fetchAll("SELECT g.id,g.name,gm.role,gm.created_at FROM group_members gm JOIN `groups` g ON gm.group_id=g.id WHERE gm.user_id=?",[$uid]);

    $export=[
        'exported_at'=>date('c'),
        'user_id'=>$uid,
        'profile'=>$profile,
        'posts'=>['count'=>count($posts),'items'=>$posts],
        'comments'=>['count'=>count($comments),'items'=>$comments],
        'likes'=>['count'=>count($likes),'items'=>$likes],
        'following'=>['count'=>count($following),'items'=>$following],
        'followers'=>['count'=>count($followers),'items'=>$followers],
        'messages_sent'=>['count'=>count($messages),'items'=>$messages],
        'wallet'=>['balance'=>intval($wallet['balance']??0),'transactions'=>$transactions],
        'saved_posts'=>['count'=>count($saved),'items'=>$saved],
        'xp_history'=>$xp,
        'badges'=>$badges,
        'groups'=>$groups,
    ];

    // Audit
    try{db()->getConnection()->prepare("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,'data_export','Full export',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    ex_ok('Dữ liệu đã được xuất',$export);
}

// Summary only (quick overview)
if($action==='summary'){
    $posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active'",[$uid])['c']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$uid])['c']);
    $likes=intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE user_id=?",[$uid])['c']);
    $following=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$uid])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);
    $messages=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=?",[$uid])['c']);
    $saved=intval($d->fetchOne("SELECT COUNT(*) as c FROM saved_posts WHERE user_id=?",[$uid])['c']);

    ex_ok('OK',['posts'=>$posts,'comments'=>$comments,'likes'=>$likes,'following'=>$following,'followers'=>$followers,'messages_sent'=>$messages,'saved_posts'=>$saved]);
}

ex_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
