<?php
// ShipperShop API v2 — Conversation Labels
// Tag conversations: work, personal, group buy, urgent, etc.
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

$DEFAULT_LABELS=[
    ['id'=>'work','name'=>'Công việc','color'=>'#3b82f6','icon'=>'💼'],
    ['id'=>'personal','name'=>'Cá nhân','color'=>'#22c55e','icon'=>'👤'],
    ['id'=>'group_buy','name'=>'Mua chung','color'=>'#f59e0b','icon'=>'🛒'],
    ['id'=>'urgent','name'=>'Gấp','color'=>'#ef4444','icon'=>'🔥'],
    ['id'=>'important','name'=>'Quan trọng','color'=>'#8b5cf6','icon'=>'⭐'],
    ['id'=>'spam','name'=>'Spam','color'=>'#94a3b8','icon'=>'🚫'],
];

function cl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cl_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// Get available labels
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='labels')){
    cl_ok('OK',$DEFAULT_LABELS);
}

// Get labels for a conversation
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='get'){
    $cid=intval($_GET['conversation_id']??0);
    if(!$cid) cl_ok('OK',[]);
    $key='conv_labels_'.$uid.'_'.$cid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $labels=$row?json_decode($row['value'],true):[];
    cl_ok('OK',$labels);
}

// Get conversations by label
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='by_label'){
    $labelId=trim($_GET['label_id']??'');
    if(!$labelId) cl_ok('OK',[]);
    $rows=$d->fetchAll("SELECT `key`,value FROM settings WHERE `key` LIKE ? AND value LIKE ?",['conv_labels_'.$uid.'_%','%"'.$labelId.'"%']);
    $convIds=[];
    foreach($rows as $r){
        preg_match('/conv_labels_\d+_(\d+)/',$r['key'],$m);
        if(isset($m[1])) $convIds[]=intval($m[1]);
    }
    cl_ok('OK',['conversation_ids'=>$convIds,'label_id'=>$labelId]);
}

// Set labels for a conversation
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $cid=intval($input['conversation_id']??0);
    $labelIds=$input['label_ids']??[];
    if(!$cid) cl_fail('Missing conversation_id');

    // Validate labels
    $validIds=array_column($DEFAULT_LABELS,'id');
    $labelIds=array_values(array_filter($labelIds,function($l) use($validIds){return in_array($l,$validIds);}));

    $key='conv_labels_'.$uid.'_'.$cid;
    $row=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($row){$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($labelIds),$key]);}
    else{$d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($labelIds)]);}

    cl_ok('Đã cập nhật nhãn',$labelIds);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
