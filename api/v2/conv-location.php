<?php
// ShipperShop API v2 — Conversation Location Sharing
// Share live/static location in conversations
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

function cl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: shared locations in conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cl_ok('OK',['locations'=>[]]);
    $key='conv_locations_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $locations=$row?json_decode($row['value'],true):[];
    // Enrich with user info
    foreach($locations as &$l){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($l['user_id']??0)]);
        if($u){$l['fullname']=$u['fullname'];$l['avatar']=$u['avatar'];}
        $l['is_expired']=isset($l['expires_at'])&&strtotime($l['expires_at'])<time();
    }unset($l);
    // Filter out expired
    $active=array_values(array_filter($locations,function($l){return !($l['is_expired']??false);}));
    cl_ok('OK',['locations'=>$active,'count'=>count($active)]);
}

// POST: share location
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $lat=floatval($input['latitude']??0);
    $lng=floatval($input['longitude']??0);
    $label=trim($input['label']??'');
    $duration=min(intval($input['duration_minutes']??60),480); // max 8h
    if(!$convId||!$lat||!$lng) cl_ok('Thieu du lieu');

    $key='conv_locations_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $locations=$row?json_decode($row['value'],true):[];

    // Remove existing from this user
    $locations=array_values(array_filter($locations,function($l) use($uid){return intval($l['user_id']??0)!==$uid;}));

    $locations[]=['user_id'=>$uid,'latitude'=>$lat,'longitude'=>$lng,'label'=>$label,'shared_at'=>date('c'),'expires_at'=>date('c',time()+$duration*60),'duration_minutes'=>$duration];

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($locations),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($locations)]);
    cl_ok('Da chia se vi tri!',['expires_at'=>date('c',time()+$duration*60)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
