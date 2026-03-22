<?php
// ShipperShop API v2 — Content Warnings
// Add content warnings/labels to posts (sensitive, graphic, spoiler)
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
$WARNING_TYPES=[
    ['id'=>'sensitive','name'=>'Nhay cam','icon'=>'⚠️','desc'=>'Noi dung nhay cam'],
    ['id'=>'graphic','name'=>'Hinh anh manh','icon'=>'🔞','desc'=>'Anh/video co the gay soc'],
    ['id'=>'spoiler','name'=>'Tiet lo noi dung','icon'=>'🙈','desc'=>'Chua thong tin tiet lo'],
    ['id'=>'controversial','name'=>'Gay tranh cai','icon'=>'🔥','desc'=>'Quan diem gay tranh cai'],
    ['id'=>'trigger','name'=>'Canh bao kich hoat','icon'=>'💔','desc'=>'Co the anh huong cam xuc'],
];

function cw_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// GET: warning types or post warnings
if($_SERVER['REQUEST_METHOD']==='GET'){
    $postId=intval($_GET['post_id']??0);
    if(!$postId) cw_ok('OK',['types'=>$WARNING_TYPES]);
    $key='post_warnings_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $warnings=$row?json_decode($row['value'],true):[];
    cw_ok('OK',['warnings'=>$warnings,'types'=>$WARNING_TYPES]);
}

// POST: add/remove warning
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    $warningId=trim($input['warning_id']??'');
    $action=$_GET['action']??'add';
    if(!$postId||!$warningId) cw_ok('Missing data');

    $key='post_warnings_'.$postId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $warnings=$row?json_decode($row['value'],true):[];

    if($action==='add'&&!in_array($warningId,$warnings)){
        $warnings[]=$warningId;
    }
    if($action==='remove'){
        $warnings=array_values(array_filter($warnings,function($w) use($warningId){return $w!==$warningId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($warnings)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($warnings))]);
    cw_ok($action==='remove'?'Da go':'Da them canh bao!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
