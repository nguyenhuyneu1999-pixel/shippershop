<?php
// ShipperShop API v2 — Feature Flags
// Toggle features on/off, per-user or global
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';$key='feature_flags';

$DEFAULTS=['stories'=>true,'polls'=>true,'reactions'=>true,'dark_mode'=>true,'smart_schedule'=>true,'engagement_predict'=>true,'template_market'=>true,'leaderboard'=>true,'ab_testing'=>false,'push_notifications'=>false,'video_upload'=>true,'voice_messages'=>false,'auto_translate'=>false,'ai_content'=>false];

function ff_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ff_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: get active flags
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='check')){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $flags=$row?array_merge($DEFAULTS,json_decode($row['value'],true)??[]):$DEFAULTS;
    // Filter for specific flag check
    $flag=$_GET['flag']??'';
    if($flag) ff_ok('OK',['flag'=>$flag,'enabled'=>$flags[$flag]??false]);
    ff_ok('OK',['flags'=>$flags,'count'=>count($flags)]);
}

// Admin: list all with descriptions
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='admin'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ff_fail('Admin only',403);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $flags=$row?array_merge($DEFAULTS,json_decode($row['value'],true)??[]):$DEFAULTS;
    $descs=['stories'=>'Tinh nang Stories','polls'=>'Binh chon trong bai viet','reactions'=>'6 emoji reactions','dark_mode'=>'Che do toi','smart_schedule'=>'Lich dang thong minh AI','engagement_predict'=>'Du doan tuong tac','template_market'=>'Mau bai viet','leaderboard'=>'Bang xep hang','ab_testing'=>'AB testing','push_notifications'=>'Thong bao day','video_upload'=>'Upload video','voice_messages'=>'Tin nhan thoai','auto_translate'=>'Tu dong dich','ai_content'=>'Noi dung AI'];
    $result=[];
    foreach($flags as $k=>$v) $result[]=['flag'=>$k,'enabled'=>$v,'description'=>$descs[$k]??$k];
    ff_ok('OK',['flags'=>$result,'count'=>count($result)]);
}

// Admin: toggle flag
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ff_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);
    $flag=trim($input['flag']??'');
    if(!$flag) ff_fail('Missing flag');

    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $flags=$row?array_merge($DEFAULTS,json_decode($row['value'],true)??[]):$DEFAULTS;
    $flags[$flag]=!($flags[$flag]??false);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($flags),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($flags)]);

    ff_ok(($flags[$flag]?'Da bat':'Da tat').' '.$flag,['flag'=>$flag,'enabled'=>$flags[$flag]]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
