<?php
// ShipperShop API v2 — Conversation Live Location
// Share and track live location within conversations for delivery coordination
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

function cll_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cll_ok('OK',['locations'=>[]]);
    $key='conv_live_loc_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $locations=$row?json_decode($row['value'],true):[];
    // Filter expired (30 min)
    $now=time();
    $active=array_values(array_filter($locations,function($l) use($now){return ($now-strtotime($l['updated_at']??''))<1800;}));
    foreach($active as &$l){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($l['user_id']??0)]);
        if($u){$l['fullname']=$u['fullname'];$l['avatar']=$u['avatar'];}
        $l['age_seconds']=$now-strtotime($l['updated_at']??'');
    }unset($l);
    cll_ok('OK',['locations'=>$active,'count'=>count($active)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $lat=floatval($input['lat']??0);
    $lng=floatval($input['lng']??0);
    $speed=floatval($input['speed']??0);
    $heading=floatval($input['heading']??0);
    if(!$convId||!$lat||!$lng) cll_ok('Missing data');

    $key='conv_live_loc_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $locations=$row?json_decode($row['value'],true):[];

    // Update or add
    $found=false;
    foreach($locations as &$l){
        if(intval($l['user_id']??0)===$uid){
            $l['lat']=$lat;$l['lng']=$lng;$l['speed']=$speed;$l['heading']=$heading;$l['updated_at']=date('c');
            $found=true;break;
        }
    }unset($l);
    if(!$found) $locations[]=['user_id'=>$uid,'lat'=>$lat,'lng'=>$lng,'speed'=>$speed,'heading'=>$heading,'started_at'=>date('c'),'updated_at'=>date('c')];

    // Clean expired
    $now=time();
    $locations=array_values(array_filter($locations,function($l) use($now){return ($now-strtotime($l['updated_at']??''))<1800;}));

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($locations),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($locations)]);

    if($action==='stop'){
        $locations=array_values(array_filter($locations,function($l) use($uid){return intval($l['user_id']??0)!==$uid;}));
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($locations),$key]);
        cll_ok('Da ngung chia se!');
    }

    cll_ok('Da cap nhat vi tri!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
