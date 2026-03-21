<?php
// ShipperShop API v2 — User Preferences
// App-level settings: font size, feed sort, language, auto-play video, etc.
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

$DEFAULTS=[
    'font_size'=>'normal',       // small, normal, large
    'feed_sort'=>'hot',          // hot, new, following
    'auto_play_video'=>true,
    'show_read_time'=>true,
    'compact_mode'=>false,
    'notif_sound'=>true,
    'dark_mode'=>'system',       // system, light, dark
    'language'=>'vi',
    'link_previews'=>true,
    'show_online_status'=>true,
];

function pf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$key='user_prefs_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $prefs=$row?json_decode($row['value'],true):[];
    // Merge with defaults
    $merged=array_merge($DEFAULTS,$prefs);
    pf_ok('OK',$merged);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    if(!$input) pf_fail('No data');

    // Get current
    $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`=?",[$key]);
    $current=$row?json_decode($row['value'],true):[];

    // Only accept known keys
    foreach($input as $k=>$v){
        if(array_key_exists($k,$DEFAULTS)) $current[$k]=$v;
    }

    if($row){
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($current),$key]);
    }else{
        $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($current)]);
    }

    $merged=array_merge($DEFAULTS,$current);
    pf_ok('Đã lưu cài đặt',$merged);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
