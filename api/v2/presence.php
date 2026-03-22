<?php
// ShipperShop API v2 — User Presence
// Real-time-ish online status for chat, batch check, last seen
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

$d=db();$action=$_GET['action']??'';

function pr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Heartbeat — update last active
if($action==='heartbeat'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $d->query("UPDATE users SET is_online=1,last_active=NOW() WHERE id=?",[$uid]);
    pr_ok('OK');
}

// Check single user
if($action==='check'){
    $tid=intval($_GET['user_id']??0);
    if(!$tid) pr_ok('OK',['online'=>false]);
    $u=$d->fetchOne("SELECT is_online,last_active FROM users WHERE id=?",[$tid]);
    if(!$u) pr_ok('OK',['online'=>false]);
    $online=intval($u['is_online'])&&$u['last_active']&&(time()-strtotime($u['last_active']))<300;
    $lastSeen=$u['last_active'];
    pr_ok('OK',['online'=>$online,'last_seen'=>$lastSeen]);
}

// Batch check (for conversation list)
if(!$action||$action==='batch'){
    $ids=$_GET['ids']??'';
    if(!$ids) pr_ok('OK',[]);
    $idArr=array_map('intval',explode(',',$ids));
    $idArr=array_filter($idArr,function($x){return $x>0;});
    if(!$idArr) pr_ok('OK',[]);
    $ph=implode(',',array_fill(0,count($idArr),'?'));
    $users=$d->fetchAll("SELECT id,is_online,last_active FROM users WHERE id IN ($ph)",$idArr);
    $result=[];
    foreach($users as $u){
        $online=intval($u['is_online'])&&$u['last_active']&&(time()-strtotime($u['last_active']))<300;
        $result[intval($u['id'])]=[ 'online'=>$online,'last_seen'=>$u['last_active']];
    }
    pr_ok('OK',$result);
}

// Conversation members presence
if($action==='conversation'){
    $uid=require_auth();
    $cid=intval($_GET['conversation_id']??0);
    if(!$cid) pr_ok('OK',[]);
    $members=$d->fetchAll("SELECT u.id,u.fullname,u.is_online,u.last_active FROM conversation_members cm JOIN users u ON cm.user_id=u.id WHERE cm.conversation_id=? AND cm.user_id!=?",[$cid,$uid]);
    // For private conversations
    if(!$members){
        $conv=$d->fetchOne("SELECT user1_id,user2_id FROM conversations WHERE id=?",[$cid]);
        if($conv){
            $otherId=intval($conv['user1_id'])===$uid?intval($conv['user2_id']):intval($conv['user1_id']);
            $members=$d->fetchAll("SELECT id,fullname,is_online,last_active FROM users WHERE id=?",[$otherId]);
        }
    }
    foreach($members as &$m){
        $m['online']=intval($m['is_online'])&&$m['last_active']&&(time()-strtotime($m['last_active']))<300;
    }unset($m);
    pr_ok('OK',$members);
}

pr_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
