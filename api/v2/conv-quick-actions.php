<?php
// ShipperShop API v2 — Conversation Quick Actions
// Pre-built actions: mark urgent, mute, label, auto-reply status
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

function cq_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Get actions/status for a conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    $key='conv_actions_'.$uid.'_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $actions=$row?json_decode($row['value'],true):['urgent'=>false,'muted'=>false,'auto_reply'=>'','label'=>''];
    cq_ok('OK',$actions);
}

// Set action
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cq_ok('Missing conversation_id');

    $key='conv_actions_'.$uid.'_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $actions=$row?json_decode($row['value'],true):['urgent'=>false,'muted'=>false,'auto_reply'=>'','label'=>''];

    // Toggle urgent
    if($action==='urgent') $actions['urgent']=!($actions['urgent']??false);
    // Toggle mute
    if($action==='mute') $actions['muted']=!($actions['muted']??false);
    // Set label
    if($action==='label') $actions['label']=trim($input['label']??'');
    // Set auto-reply
    if($action==='auto_reply') $actions['auto_reply']=trim($input['message']??'');
    // Clear all
    if($action==='clear') $actions=['urgent'=>false,'muted'=>false,'auto_reply'=>'','label'=>''];

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($actions),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($actions)]);

    $msgs=['urgent'=>($actions['urgent']?'Da danh dau khan cap':'Da bo danh dau'),'mute'=>($actions['muted']?'Da tat thong bao':'Da bat thong bao'),'label'=>'Da gan nhan','auto_reply'=>'Da thiet lap tu dong tra loi','clear'=>'Da xoa tat ca'];
    cq_ok($msgs[$action]??'OK',$actions);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
