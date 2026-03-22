<?php
// ShipperShop API v2 — Conversation Reactions Summary
// Aggregate reaction stats across all messages in a conversation
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

function crs2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$convId=intval($_GET['conversation_id']??0);
if(!$convId) crs2_ok('OK',['summary'=>[]]);

// Get all reaction data from settings
$key='msg_reactions_conv_'.$convId;
$row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
$allReactions=$row?json_decode($row['value'],true):[];

// Aggregate by emoji
$emojiCounts=[];$userCounts=[];$totalReactions=0;
foreach($allReactions as $msgId=>$reactions){
    if(!is_array($reactions)) continue;
    foreach($reactions as $r){
        $emoji=$r['emoji']??'';
        $userId=intval($r['user_id']??0);
        if($emoji){$emojiCounts[$emoji]=($emojiCounts[$emoji]??0)+1;$totalReactions++;}
        if($userId) $userCounts[$userId]=($userCounts[$userId]??0)+1;
    }
}

arsort($emojiCounts);
arsort($userCounts);

// Top reactors
$topReactors=[];
foreach(array_slice($userCounts,0,5,true) as $userId=>$count){
    $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[$userId]);
    if($u) $topReactors[]=['user_id'=>$userId,'fullname'=>$u['fullname'],'avatar'=>$u['avatar'],'count'=>$count];
}

crs2_ok('OK',['emoji_counts'=>$emojiCounts,'total_reactions'=>$totalReactions,'top_reactors'=>$topReactors,'messages_with_reactions'=>count($allReactions)]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
