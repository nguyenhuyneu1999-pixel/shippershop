<?php
// ShipperShop API v2 — Delivery Rating
// Rate delivery experience: speed, packaging, communication, overall
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

function dr3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$targetId=intval($_GET['user_id']??0);

if($_SERVER['REQUEST_METHOD']==='GET'){
    if(!$targetId) dr3_ok('Missing user_id');
    $key='delivery_ratings_'.$targetId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $ratings=$row?json_decode($row['value'],true):[];
    $total=count($ratings);
    $avgSpeed=$total>0?round(array_sum(array_column($ratings,'speed'))/$total,1):0;
    $avgPackaging=$total>0?round(array_sum(array_column($ratings,'packaging'))/$total,1):0;
    $avgComm=$total>0?round(array_sum(array_column($ratings,'communication'))/$total,1):0;
    $avgOverall=$total>0?round(array_sum(array_column($ratings,'overall'))/$total,1):0;
    $dist=[1=>0,2=>0,3=>0,4=>0,5=>0];
    foreach($ratings as $r){$s=intval($r['overall']??0);if($s>=1&&$s<=5)$dist[$s]++;}
    dr3_ok('OK',['ratings'=>array_slice($ratings,0,20),'averages'=>['speed'=>$avgSpeed,'packaging'=>$avgPackaging,'communication'=>$avgComm,'overall'=>$avgOverall],'distribution'=>$dist,'total'=>$total]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $targetId=intval($input['user_id']??0);
    if(!$targetId||$targetId===$uid) dr3_ok('Invalid target');
    $key='delivery_ratings_'.$targetId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $ratings=$row?json_decode($row['value'],true):[];
    $speed=max(1,min(5,intval($input['speed']??5)));
    $packaging=max(1,min(5,intval($input['packaging']??5)));
    $comm=max(1,min(5,intval($input['communication']??5)));
    $overall=max(1,min(5,intval($input['overall']??5)));
    $comment=trim($input['comment']??'');
    $maxId=0;foreach($ratings as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
    array_unshift($ratings,['id'=>$maxId+1,'rater_id'=>$uid,'speed'=>$speed,'packaging'=>$packaging,'communication'=>$comm,'overall'=>$overall,'comment'=>$comment,'created_at'=>date('c')]);
    if(count($ratings)>200) $ratings=array_slice($ratings,0,200);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($ratings),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($ratings)]);
    dr3_ok('Da danh gia!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
