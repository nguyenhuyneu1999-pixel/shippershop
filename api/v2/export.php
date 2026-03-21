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

function ex_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ex_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// Export overview (what data we have)
if($action==='overview'){
    $posts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=?",[$uid])['c']);
    $comments=intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=?",[$uid])['c']);
    $likes=intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE user_id=?",[$uid])['c']);
    $messages=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=?",[$uid])['c']);
    $followers=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);
    $following=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$uid])['c']);
    ex_ok('OK',[
        'posts'=>$posts,'comments'=>$comments,'likes'=>$likes,
        'messages'=>$messages,'followers'=>$followers,'following'=>$following,
        'note'=>'Sử dụng action=full để tải toàn bộ dữ liệu (JSON)'
    ]);
}

// Full export
if(!$action||$action==='full'){
    rate_enforce('data_export',2,86400); // Max 2 exports per day

    $user=$d->fetchOne("SELECT id,fullname,email,phone,avatar,bio,shipping_company,is_verified,created_at FROM users WHERE id=?",[$uid]);
    $posts=$d->fetchAll("SELECT id,content,type,province,district,likes_count,comments_count,created_at FROM posts WHERE user_id=? AND `status`!='deleted' ORDER BY created_at DESC",[$uid]);
    $comments=$d->fetchAll("SELECT c.id,c.content,c.post_id,c.created_at FROM comments c WHERE c.user_id=? ORDER BY c.created_at DESC LIMIT 500",[$uid]);
    $likes=$d->fetchAll("SELECT post_id,created_at FROM likes WHERE user_id=? ORDER BY created_at DESC LIMIT 1000",[$uid]);
    $saved=$d->fetchAll("SELECT post_id,created_at FROM saved_posts WHERE user_id=? ORDER BY created_at DESC",[$uid]);
    $followers=$d->fetchAll("SELECT u.id,u.fullname FROM follows f JOIN users u ON f.follower_id=u.id WHERE f.following_id=? AND u.`status`='active'",[$uid]);
    $following=$d->fetchAll("SELECT u.id,u.fullname FROM follows f JOIN users u ON f.following_id=u.id WHERE f.follower_id=? AND u.`status`='active'",[$uid]);
    $groups=$d->fetchAll("SELECT g.id,g.name,gm.role FROM group_members gm JOIN `groups` g ON gm.group_id=g.id WHERE gm.user_id=?",[$uid]);
    $wallet=$d->fetchOne("SELECT balance FROM wallets WHERE user_id=?",[$uid]);
    $transactions=$d->fetchAll("SELECT type,amount,description,created_at FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$uid]);
    $xp=$d->fetchAll("SELECT action,xp,detail,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT 200",[$uid]);

    // Audit log
    try{$d->query("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,'data_export','Full export',?,NOW())",[$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    ex_ok('Dữ liệu của bạn',[
        'exported_at'=>date('c'),
        'user'=>$user,
        'posts'=>$posts,
        'comments'=>$comments,
        'likes'=>$likes,
        'saved_posts'=>$saved,
        'followers'=>$followers,
        'following'=>$following,
        'groups'=>$groups,
        'wallet'=>['balance'=>$wallet?intval($wallet['balance']):0],
        'transactions'=>$transactions,
        'xp_history'=>$xp,
    ]);
}

ex_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
