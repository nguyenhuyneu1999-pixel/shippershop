<?php
// ShipperShop API v2 — Follow Requests
// Private accounts: follow requires approval
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function fr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fr_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// Get pending follow requests (people wanting to follow me)
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='pending')){
    // Store in settings since we don't have a follow_requests table
    $key='follow_requests_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $requests=$row?json_decode($row['value'],true):[];
    // Enrich with user info
    $enriched=[];
    foreach($requests as $r){
        if(($r['status']??'')!=='pending') continue;
        $u=$d->fetchOne("SELECT id,fullname,avatar,shipping_company FROM users WHERE id=?",[$r['from_user_id']??0]);
        if($u){$r['user']=$u;$enriched[]=$r;}
    }
    fr_ok('OK',['requests'=>$enriched,'count'=>count($enriched)]);
}

// Send follow request
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='send')){
    $input=json_decode(file_get_contents('php://input'),true);
    $targetId=intval($input['user_id']??0);
    if(!$targetId||$targetId===$uid) fr_fail('Invalid user');

    // Check if target is private
    $target=$d->fetchOne("SELECT id,fullname FROM users WHERE id=? AND `status`='active'",[$targetId]);
    if(!$target) fr_fail('User not found',404);

    // Check target's privacy setting
    $privKey='user_prefs_'.$targetId;
    $privRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$privKey]);
    $prefs=$privRow?json_decode($privRow['value'],true):[];
    $isPrivate=!empty($prefs['private_account']);

    if(!$isPrivate){
        // Public account — just follow directly
        try{$pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id,created_at) VALUES (?,?,NOW())")->execute([$uid,$targetId]);}catch(\Throwable $e){}
        fr_ok('Da theo doi!',['followed'=>true,'pending'=>false]);
    }

    // Private account — create request
    $key='follow_requests_'.$targetId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $requests=$row?json_decode($row['value'],true):[];

    // Check duplicate
    foreach($requests as $r){
        if(intval($r['from_user_id']??0)===$uid&&($r['status']??'')==='pending')
            fr_ok('Da gui yeu cau truoc do',['pending'=>true]);
    }

    $requests[]=['from_user_id'=>$uid,'status'=>'pending','created_at'=>date('c')];
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($requests),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($requests)]);

    // Notify target
    try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'follow_request','Yeu cau theo doi',?,?,NOW())")->execute([$targetId,getUserName($uid).' muon theo doi ban',json_encode(['user_id'=>$uid])]);}catch(\Throwable $e){}

    fr_ok('Da gui yeu cau!',['pending'=>true]);
}

// Accept/reject request
if($_SERVER['REQUEST_METHOD']==='POST'&&($action==='accept'||$action==='reject')){
    $input=json_decode(file_get_contents('php://input'),true);
    $fromId=intval($input['user_id']??0);
    if(!$fromId) fr_fail('Missing user_id');

    $key='follow_requests_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $requests=$row?json_decode($row['value'],true):[];

    foreach($requests as &$r){
        if(intval($r['from_user_id']??0)===$fromId&&($r['status']??'')==='pending'){
            $r['status']=$action==='accept'?'accepted':'rejected';
            $r['resolved_at']=date('c');
        }
    }unset($r);

    $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($requests),$key]);

    if($action==='accept'){
        try{$pdo->prepare("INSERT IGNORE INTO follows (follower_id,following_id,created_at) VALUES (?,?,NOW())")->execute([$fromId,$uid]);}catch(\Throwable $e){}
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'follow_accepted','Yeu cau duoc chap nhan',?,?,NOW())")->execute([$fromId,getUserName($uid).' da chap nhan yeu cau theo doi',json_encode(['user_id'=>$uid])]);}catch(\Throwable $e){}
    }

    fr_ok($action==='accept'?'Da chap nhan':'Da tu choi');
}

fr_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

function getUserName($uid){$u=db()->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);return $u?$u['fullname']:'User';}
