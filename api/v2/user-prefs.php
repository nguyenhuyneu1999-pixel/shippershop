<?php
// ShipperShop API v2 — User Preferences Sync
// Store/retrieve user settings server-side for cross-device sync
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function up_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='user_prefs_'.$uid;

// GET: retrieve all preferences
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $defaults=['theme'=>'auto','font_size'=>'normal','language'=>'vi','notifications_sound'=>true,'notifications_vibrate'=>true,'auto_play_video'=>true,'compact_feed'=>false,'show_online_status'=>true,'read_receipts'=>true,'default_post_type'=>'post','timezone'=>'Asia/Ho_Chi_Minh'];
    $prefs=$row?array_merge($defaults,json_decode($row['value'],true)??[]):$defaults;
    up_ok('OK',$prefs);
}

// POST: update preferences
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    if(!$input) up_ok('No data');

    // Merge with existing
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $prefs=$row?json_decode($row['value'],true):[];
    $allowed=['theme','font_size','language','notifications_sound','notifications_vibrate','auto_play_video','compact_feed','show_online_status','read_receipts','default_post_type','timezone','sidebar_collapsed','posts_per_page'];
    foreach($allowed as $k){
        if(array_key_exists($k,$input)) $prefs[$k]=$input[$k];
    }
    $prefs['updated_at']=date('c');

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);

    up_ok('Đã lưu cài đặt',$prefs);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
