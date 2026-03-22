<?php
// ShipperShop API v2 — Online Privacy Settings
// Control who sees your online status, last seen, activity
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

function op_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='privacy_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $defaults=['show_online'=>true,'show_last_seen'=>true,'show_read_receipts'=>true,'show_typing'=>true,'private_account'=>false,'hide_from_search'=>false];
    $prefs=$row?array_merge($defaults,json_decode($row['value'],true)??[]):$defaults;
    op_ok('OK',$prefs);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $prefs=$row?json_decode($row['value'],true):[];
    $allowed=['show_online','show_last_seen','show_read_receipts','show_typing','private_account','hide_from_search'];
    foreach($allowed as $k){if(array_key_exists($k,$input))$prefs[$k]=!!$input[$k];}

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);
    op_ok('Da luu',$prefs);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
