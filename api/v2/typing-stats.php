<?php
// ShipperShop API v2 — Typing/Chat Activity Stats
// Message activity: busiest hours, avg response time, active conversations
// session removed: JWT auth only
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

function ts_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

$data=cache_remember('chat_stats_'.$uid, function() use($d,$uid) {
    // Total messages sent
    $sent=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE sender_id=?",[$uid])['c']);
    $received=intval($d->fetchOne("SELECT COUNT(*) as c FROM messages WHERE conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id=?) AND sender_id!=?",[$uid,$uid])['c']);
    $convCount=intval($d->fetchOne("SELECT COUNT(*) as c FROM conversation_members WHERE user_id=?",[$uid])['c']);

    // Busiest hours
    $hours=$d->fetchAll("SELECT HOUR(created_at) as h,COUNT(*) as c FROM messages WHERE sender_id=? GROUP BY HOUR(created_at) ORDER BY c DESC LIMIT 5",[$uid]);

    // Most chatted with
    $topChats=$d->fetchAll("SELECT cm2.user_id,u.fullname,u.avatar,COUNT(m.id) as msg_count FROM conversation_members cm1 JOIN conversation_members cm2 ON cm1.conversation_id=cm2.conversation_id JOIN messages m ON m.conversation_id=cm1.conversation_id JOIN users u ON cm2.user_id=u.id WHERE cm1.user_id=? AND cm2.user_id!=? GROUP BY cm2.user_id ORDER BY msg_count DESC LIMIT 5",[$uid,$uid]);

    // Messages per day (last 30 days)
    $daily=$d->fetchAll("SELECT DATE(created_at) as day,COUNT(*) as count FROM messages WHERE sender_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY day",[$uid]);

    return ['sent'=>$sent,'received'=>$received,'conversations'=>$convCount,'busiest_hours'=>$hours,'top_contacts'=>$topChats,'daily'=>$daily,'avg_per_day'=>count($daily)?round(array_sum(array_column($daily,'count'))/count($daily),1):0];
}, 300);

ts_ok('OK',$data);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
