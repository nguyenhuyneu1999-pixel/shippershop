<?php
// ShipperShop API v2 — Conversation Summary
// AI-style summary of conversation: key points, action items, sentiment
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function cvs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$convId=intval($_GET['conversation_id']??0);
if(!$convId) cvs_ok('OK',['summary'=>null]);

$msgs=$d->fetchAll("SELECT m.content,m.sender_id,m.created_at,u.fullname FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.conversation_id=? ORDER BY m.created_at DESC LIMIT 50",[$convId]);
if(!$msgs) cvs_ok('OK',['summary'=>null,'message_count'=>0]);

$totalMsgs=count($msgs);
$participants=[];
$allText='';
$actionKeywords=['can','phai','nen','nho','gui','giao','lam','goi','den','gap','tra'];
$actionItems=[];

foreach($msgs as $m){
    $pid=intval($m['sender_id']);
    $participants[$pid]=($participants[$pid]??0)+1;
    $allText.=' '.mb_strtolower($m['content']??'');
    // Extract potential action items
    foreach($actionKeywords as $kw){
        if(mb_strpos(mb_strtolower($m['content']??''),$kw)!==false&&mb_strlen($m['content'])>=10&&mb_strlen($m['content'])<=200){
            $actionItems[]=['text'=>$m['content'],'by'=>$m['fullname'],'at'=>$m['created_at']];
            break;
        }
    }
}

// Simple sentiment
$positive=['cam on','vui','tot','ok','duoc','hay','tuyet','yeu','thich'];
$negative=['buon','chan','toi','xau','khong duoc','te','loi','hong'];
$posCount=0;$negCount=0;
foreach($positive as $w){$posCount+=mb_substr_count($allText,$w);}
foreach($negative as $w){$negCount+=mb_substr_count($allText,$w);}
$sentiment=$posCount>$negCount?'positive':($negCount>$posCount?'negative':'neutral');

$participantList=[];
foreach($participants as $pid=>$count){
    $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[$pid]);
    if($u) $participantList[]=['user_id'=>$pid,'fullname'=>$u['fullname'],'avatar'=>$u['avatar'],'messages'=>$count];
}
usort($participantList,function($a,$b){return $b['messages']-$a['messages'];});

// Time span
$first=$msgs[count($msgs)-1]['created_at']??'';
$last=$msgs[0]['created_at']??'';

cvs_ok('OK',['message_count'=>$totalMsgs,'participants'=>$participantList,'sentiment'=>$sentiment,'action_items'=>array_slice($actionItems,0,5),'time_span'=>['first'=>$first,'last'=>$last]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
