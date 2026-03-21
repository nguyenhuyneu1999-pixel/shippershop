<?php
// ShipperShop API v2 — User Theme Settings
// Dark/light/auto theme + custom accent color per user
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

function ut_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='user_theme_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $defaults=['mode'=>'auto','accent'=>'#7C3AED','font_size'=>16,'compact'=>false,'reduce_animations'=>false,'high_contrast'=>false];
    $theme=$row?array_merge($defaults,json_decode($row['value'],true)??[]):$defaults;
    ut_ok('OK',$theme);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $theme=$row?json_decode($row['value'],true):[];
    $allowed=['mode','accent','font_size','compact','reduce_animations','high_contrast'];
    foreach($allowed as $k){if(array_key_exists($k,$input)) $theme[$k]=$input[$k];}
    // Validate accent color
    if(isset($theme['accent'])&&!preg_match('/^#[0-9a-fA-F]{6}$/',$theme['accent'])) $theme['accent']='#7C3AED';
    // Validate font_size
    if(isset($theme['font_size'])) $theme['font_size']=max(12,min(24,intval($theme['font_size'])));

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($theme),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($theme)]);
    ut_ok('Da luu!',$theme);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
