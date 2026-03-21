<?php
// ShipperShop API v2 — User Data Export (GDPR/privacy compliance)
// Users can download all their data as JSON
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$uid=require_auth();
$action=$_GET['action']??'';

// Rate limit: 1 export per hour
rate_enforce('data_export',1,3600);

try {

if($action==='download'||!$action){
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="shippershop-data-'.$uid.'-'.date('Y-m-d').'.json"');

    $data = [];

    // Profile
    $data['profile'] = $d->fetchOne("SELECT id,fullname,email,phone,avatar,cover_image,bio,shipping_company,is_verified,created_at FROM users WHERE id=?",[$uid]);

    // Posts
    $data['posts'] = $d->fetchAll("SELECT id,content,type,images,video_url,province,district,likes_count,comments_count,shares_count,created_at FROM posts WHERE user_id=? AND `status`!='deleted' ORDER BY created_at DESC",[$uid]);

    // Comments
    $data['comments'] = $d->fetchAll("SELECT id,post_id,content,likes_count,created_at FROM comments WHERE user_id=? AND `status`='active' ORDER BY created_at DESC LIMIT 1000",[$uid]);

    // Likes
    $data['likes'] = $d->fetchAll("SELECT post_id,created_at FROM likes WHERE user_id=? ORDER BY created_at DESC LIMIT 5000",[$uid]);

    // Saved posts
    $data['saved_posts'] = $d->fetchAll("SELECT post_id,created_at FROM saved_posts WHERE user_id=? ORDER BY created_at DESC",[$uid]);

    // Messages (own messages only)
    $data['messages_sent'] = $d->fetchAll("SELECT id,conversation_id,content,created_at FROM messages WHERE sender_id=? ORDER BY created_at DESC LIMIT 2000",[$uid]);

    // Follows
    $data['following'] = $d->fetchAll("SELECT following_id,created_at FROM follows WHERE follower_id=?",[$uid]);
    $data['followers'] = $d->fetchAll("SELECT follower_id,created_at FROM follows WHERE following_id=?",[$uid]);

    // Groups
    $data['groups'] = $d->fetchAll("SELECT g.id,g.name,gm.role,gm.joined_at FROM group_members gm JOIN groups g ON gm.group_id=g.id WHERE gm.user_id=?",[$uid]);

    // Wallet
    $data['wallet'] = $d->fetchOne("SELECT balance,created_at FROM wallets WHERE user_id=?",[$uid]);
    $data['transactions'] = $d->fetchAll("SELECT type,amount,description,created_at FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 500",[$uid]);

    // Gamification
    $data['xp'] = $d->fetchAll("SELECT action,xp,detail,created_at FROM user_xp WHERE user_id=? ORDER BY created_at DESC LIMIT 500",[$uid]);
    $data['badges'] = $d->fetchAll("SELECT badge_id,earned_at FROM user_badges WHERE user_id=?",[$uid]);
    $data['streak'] = $d->fetchOne("SELECT current_streak,longest_streak,last_active_date FROM user_streaks WHERE user_id=?",[$uid]);

    // Stories
    $data['stories'] = $d->fetchAll("SELECT content,image_url,background,view_count,expires_at,created_at FROM stories WHERE user_id=? ORDER BY created_at DESC LIMIT 100",[$uid]);

    // Bookmark collections
    $data['bookmark_collections'] = $d->fetchAll("SELECT name,icon,post_count,created_at FROM bookmark_collections WHERE user_id=?",[$uid]);

    // Activity summary
    $data['export_meta'] = [
        'exported_at' => date('c'),
        'user_id' => $uid,
        'format' => 'JSON',
        'version' => '2.2',
    ];

    // Audit log
    try{$pdo=db()->getConnection();$pdo->prepare("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,'data_export','Full data export',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Summary (no download, just counts)
if($action==='summary'){
    header('Content-Type: application/json; charset=utf-8');
    $summary = [
        'posts' => intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`!='deleted'",[$uid])['c']),
        'comments' => intval($d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id=? AND `status`='active'",[$uid])['c']),
        'likes' => intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE user_id=?",[$uid])['c']),
        'messages' => intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=?",[$uid])['c']),
        'following' => intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id=?",[$uid])['c']),
        'followers' => intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']),
        'groups' => intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE user_id=?",[$uid])['c']),
        'stories' => intval($d->fetchOne("SELECT COUNT(*) as c FROM stories WHERE user_id=?",[$uid])['c']),
    ];
    echo json_encode(['success'=>true,'data'=>$summary],JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Invalid action']);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
