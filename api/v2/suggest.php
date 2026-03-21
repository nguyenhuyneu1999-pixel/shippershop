<?php
// ShipperShop API v2 — Search Suggestions (autocomplete)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$q=trim($_GET['q']??'');

if(mb_strlen($q)<1){
    echo json_encode(['success'=>true,'data'=>[]]);exit;
}

try {
    $data=[];

    // Users (top 5)
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 5",['%'.$q.'%']);
    foreach($users as $u){$u['type']='user';$data[]=$u;}

    // Groups (top 3)
    $groups=$d->fetchAll("SELECT id,name as fullname,avatar FROM `groups` WHERE name LIKE ? LIMIT 3",['%'.$q.'%']);
    foreach($groups as $g){$g['type']='group';$g['shipping_company']='';$data[]=$g;}

    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
