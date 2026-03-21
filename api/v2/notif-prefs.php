<?php
// ShipperShop API v2 — Notification Preferences
// Users can mute certain notification types, set quiet hours
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

function np_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function np_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get current preferences (stored in settings table as JSON)
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['notif_prefs_'.$uid]);
    $defaults=['likes'=>true,'comments'=>true,'follows'=>true,'messages'=>true,'groups'=>true,'system'=>true,'marketing'=>false,'quiet_start'=>null,'quiet_end'=>null];
    $prefs=$row?array_merge($defaults,json_decode($row['value'],true)):$defaults;
    np_ok('OK',$prefs);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $allowed=['likes','comments','follows','messages','groups','system','marketing','quiet_start','quiet_end'];
    $prefs=[];
    foreach($allowed as $k){
        if(isset($input[$k])){
            $prefs[$k]=in_array($k,['quiet_start','quiet_end'])?$input[$k]:(bool)$input[$k];
        }
    }

    // Upsert
    $key='notif_prefs_'.$uid;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing){
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);
    }else{
        $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);
    }

    np_ok('Đã lưu cài đặt thông báo',$prefs);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
