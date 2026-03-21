<?php
// ShipperShop API v2 — Server-Sent Events (SSE)
// Real-time stream: new notifications, messages, typing indicators
// Client: new EventSource('/api/v2/sse.php?token=JWT')
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no'); // Nginx/LiteSpeed

// Auth via query param (EventSource can't send headers)
$token=$_GET['token']??'';
if(!$token){echo "event: error\ndata: {\"message\":\"Missing token\"}\n\n";exit;}

// Verify JWT manually
$parts=explode('.',$token);
if(count($parts)!==3){echo "event: error\ndata: {\"message\":\"Invalid token\"}\n\n";exit;}
$payload=json_decode(base64_decode($parts[1]),true);
$uid=intval($payload['user_id']??0);
if(!$uid){echo "event: error\ndata: {\"message\":\"Invalid user\"}\n\n";exit;}

// Verify signature
$secret=defined('JWT_SECRET')?JWT_SECRET:'12a45d2132717424f75b21e997949331';
$expectedSig=base64_encode(hash_hmac('sha256',$parts[0].'.'.$parts[1],$secret,true));
if(!hash_equals($expectedSig,$parts[2])){echo "event: error\ndata: {\"message\":\"Invalid signature\"}\n\n";exit;}

$d=db();

// Update online status
$d->query("UPDATE users SET is_online=1,last_active=NOW() WHERE id=?",[$uid]);

// Send initial connection event
echo "event: connected\ndata: {\"user_id\":$uid,\"time\":\"".date('H:i:s')."\"}\n\n";
ob_flush();flush();

// Track last check times
$lastNotifCheck=time();
$lastMsgCheck=time();
$lastNotifId=0;
$lastMsgId=0;

// Get initial counts
$notifRow=$d->fetchOne("SELECT MAX(id) as m FROM notifications WHERE user_id=?",[$uid]);
$lastNotifId=intval($notifRow['m']??0);
$msgRow=$d->fetchOne("SELECT MAX(m.id) as m FROM messages m JOIN conversations c ON m.conversation_id=c.id WHERE (c.user1_id=? OR c.user2_id=?) AND m.sender_id!=?",[$uid,$uid,$uid]);
$lastMsgId=intval($msgRow['m']??0);

$maxRuntime=25; // seconds (keep under 30s shared hosting limit)
$start=time();
$interval=3; // check every 3 seconds

while((time()-$start)<$maxRuntime){
    // Check new notifications
    $newNotifs=$d->fetchAll("SELECT id,type,title,message,data,created_at FROM notifications WHERE user_id=? AND id>? ORDER BY id DESC LIMIT 5",[$uid,$lastNotifId]);
    if($newNotifs){
        foreach($newNotifs as $n){
            $lastNotifId=max($lastNotifId,intval($n['id']));
            echo "event: notification\ndata: ".json_encode($n,JSON_UNESCAPED_UNICODE)."\n\n";
        }
        ob_flush();flush();
    }

    // Check new messages
    $newMsgs=$d->fetchAll("SELECT m.id,m.conversation_id,m.sender_id,m.content,m.created_at,u.fullname as sender_name FROM messages m JOIN conversations c ON m.conversation_id=c.id LEFT JOIN users u ON m.sender_id=u.id WHERE (c.user1_id=? OR c.user2_id=?) AND m.sender_id!=? AND m.id>? ORDER BY m.id DESC LIMIT 5",[$uid,$uid,$uid,$lastMsgId]);
    if($newMsgs){
        foreach($newMsgs as $m){
            $lastMsgId=max($lastMsgId,intval($m['id']));
            echo "event: message\ndata: ".json_encode($m,JSON_UNESCAPED_UNICODE)."\n\n";
        }
        ob_flush();flush();
    }

    // Heartbeat every cycle
    if(connection_aborted()) break;
    sleep($interval);

    // Update online
    if((time()-$start)%15<$interval){
        $d->query("UPDATE users SET is_online=1,last_active=NOW() WHERE id=?",[$uid]);
    }
}

// Final event
echo "event: reconnect\ndata: {\"after\":2}\n\n";
ob_flush();flush();
