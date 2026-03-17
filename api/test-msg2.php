<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth-check.php';
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors',1);
$d=db();
$userId=getAuthUserId();
echo "userId: $userId\n\n";

// Run EXACT same query as conversations API
$sf='active';
try{
$convs=$d->fetchAll("SELECT c.*, c.status as conv_status,
    CASE WHEN c.user1_id=? THEN u2.id ELSE u1.id END as other_id,
    CASE WHEN c.user1_id=? THEN u2.fullname ELSE u1.fullname END as other_name,
    CASE WHEN c.user1_id=? THEN u2.avatar ELSE u1.avatar END as other_avatar,
    CASE WHEN c.user1_id=? THEN u2.is_online ELSE u1.is_online END as other_online,
    CASE WHEN c.user1_id=? THEN u2.last_active ELSE u1.last_active END as other_last_active,
    CASE WHEN c.user1_id=? THEN u2.shipping_company ELSE u1.shipping_company END as other_ship,
    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id=c.id AND m.sender_id!=? AND m.is_read=0) as unread_count
    FROM conversations c JOIN users u1 ON c.user1_id=u1.id JOIN users u2 ON c.user2_id=u2.id
    WHERE (c.user1_id=? OR c.user2_id=?) AND c.status=?
    ORDER BY c.last_message_at DESC",
    [$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId,$userId,$sf]);
echo "Conversations query OK! Count: ".count($convs)."\n";
foreach($convs as $c) echo "  other_name=".$c['other_name']." ship=".$c['other_ship']."\n";
}catch(Throwable $e){echo "CONV ERROR: ".$e->getMessage()."\n";}

// Test follows query
try{
$fr=$d->fetchAll("SELECT DISTINCT u.id,u.fullname FROM follows f1 JOIN follows f2 ON f1.following_id=f2.follower_id AND f1.follower_id=f2.following_id JOIN users u ON f1.following_id=u.id WHERE f1.follower_id=? AND u.id!=?",[$userId,$userId]);
echo "\nMutual follows: ".count($fr)."\n";
}catch(Throwable $e){echo "FOLLOWS ERROR: ".$e->getMessage()."\n";}

// Test user_info
try{
$u=$d->fetchOne("SELECT id,fullname,shipping_company,is_online FROM users WHERE id=3",[]);
echo "\nUser3: ".json_encode($u)."\n";
}catch(Throwable $e){echo "USER ERROR: ".$e->getMessage()."\n";}
