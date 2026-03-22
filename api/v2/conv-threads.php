<?php
// ShipperShop API v2 — Conversation Threads
// Reply to specific messages (threaded conversations like Slack)
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

function ct_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Get thread (replies to a specific message)
if($_SERVER['REQUEST_METHOD']==='GET'){
    $parentId=intval($_GET['parent_id']??0);
    if(!$parentId) ct_ok('OK',['replies'=>[]]);
    $key='msg_thread_'.$parentId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $replies=$row?json_decode($row['value'],true):[];
    // Enrich
    foreach($replies as &$r){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($r['user_id']??0)]);
        if($u){$r['fullname']=$u['fullname'];$r['avatar']=$u['avatar'];}
    }unset($r);
    ct_ok('OK',['replies'=>$replies,'count'=>count($replies)]);
}

// Reply to message (create thread)
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $parentId=intval($input['parent_id']??0);
    $content=trim($input['content']??'');
    if(!$parentId||!$content) ct_ok('Missing data');

    $key='msg_thread_'.$parentId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $replies=$row?json_decode($row['value'],true):[];

    $maxId=0;foreach($replies as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
    $replies[]=['id'=>$maxId+1,'user_id'=>$uid,'content'=>$content,'created_at'=>date('c')];

    if(count($replies)>100) ct_ok('Thread qua dai');

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($replies)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($replies))]);

    ct_ok('Da tra loi!',['id'=>$maxId+1,'thread_count'=>count($replies)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
