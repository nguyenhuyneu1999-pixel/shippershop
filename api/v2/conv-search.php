<?php
// ShipperShop API v2 — Conversation Search
// Search through messages, filter by conversation, date range
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function cs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cs_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// Search messages
if(!$action||$action==='search'){
    $q=trim($_GET['q']??'');
    if(mb_strlen($q)<2) cs_fail('Tìm kiếm tối thiểu 2 ký tự');
    $convId=intval($_GET['conversation_id']??0);
    $limit=min(intval($_GET['limit']??20),50);

    $w=["m.content LIKE ?","cm.user_id=?"];
    $p=['%'.$q.'%',$uid];
    if($convId){$w[]="m.conversation_id=?";$p[]=$convId;}

    $wc=implode(' AND ',$w);
    $messages=$d->fetchAll("SELECT m.*,u.fullname as sender_name,u.avatar as sender_avatar,c.id as conv_id FROM messages m JOIN conversation_members cm ON m.conversation_id=cm.conversation_id JOIN users u ON m.sender_id=u.id JOIN conversations c ON m.conversation_id=c.id WHERE $wc ORDER BY m.created_at DESC LIMIT $limit",$p);

    // Group by conversation
    $grouped=[];
    foreach($messages as $m){
        $cid=intval($m['conv_id']);
        if(!isset($grouped[$cid])){$grouped[$cid]=['conversation_id'=>$cid,'messages'=>[]];}
        $grouped[$cid]['messages'][]=$m;
    }

    cs_ok('OK',['results'=>array_values($grouped),'total'=>count($messages),'query'=>$q]);
}

// Search conversations by participant name
if($action==='find_conversation'){
    $q=trim($_GET['q']??'');
    if(mb_strlen($q)<1) cs_fail('Nhập tên');
    $convs=$d->fetchAll("SELECT c.id,c.`status`,c.updated_at,u.fullname,u.avatar FROM conversations c JOIN conversation_members cm1 ON c.id=cm1.conversation_id JOIN conversation_members cm2 ON c.id=cm2.conversation_id JOIN users u ON cm2.user_id=u.id WHERE cm1.user_id=? AND cm2.user_id!=? AND u.fullname LIKE ? ORDER BY c.updated_at DESC LIMIT 10",[$uid,$uid,'%'.$q.'%']);
    cs_ok('OK',$convs);
}

cs_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
