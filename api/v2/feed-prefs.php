<?php
// ShipperShop API v2 — Feed Preferences
// Mute users from feed, hide post types, personalization
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

function fp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fp_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get feed preferences
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['feed_prefs_'.$uid]);
    $defaults=['muted_users'=>[],'hidden_types'=>[],'show_verified_first'=>false,'compact_mode'=>false];
    $prefs=$row?array_merge($defaults,json_decode($row['value'],true)??[]):$defaults;
    fp_ok('OK',$prefs);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Mute/unmute user from feed
    if($action==='mute_user'){
        $tid=intval($input['user_id']??0);
        if(!$tid) fp_fail('Missing user_id');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['feed_prefs_'.$uid]);
        $prefs=$row?json_decode($row['value'],true):[];
        $muted=$prefs['muted_users']??[];
        $idx=array_search($tid,$muted);
        if($idx!==false){array_splice($muted,$idx,1);$msg='Đã bỏ ẩn';}
        else{$muted[]=$tid;$msg='Đã ẩn khỏi feed';}
        $prefs['muted_users']=$muted;
        $key='feed_prefs_'.$uid;
        $exists=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($exists) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);
        fp_ok($msg,['muted'=>$idx===false]);
    }

    // Update preferences
    if($action==='update'){
        $allowed=['hidden_types','show_verified_first','compact_mode'];
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['feed_prefs_'.$uid]);
        $prefs=$row?json_decode($row['value'],true):[];
        foreach($allowed as $k){
            if(isset($input[$k])) $prefs[$k]=$input[$k];
        }
        $key='feed_prefs_'.$uid;
        $exists=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($exists) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);
        fp_ok('Đã lưu',$prefs);
    }

    fp_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
