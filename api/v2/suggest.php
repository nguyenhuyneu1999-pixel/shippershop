<?php
// ShipperShop API v2 — Search Suggestions
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$q=trim($_GET['q']??'');
if(mb_strlen($q)<1){echo json_encode(['success'=>true,'data'=>[]]);exit;}

try {
    $d=db();$data=[];
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 5",['%'.$q.'%']);
    foreach($users as $u){$u['type']='user';$data[]=$u;}
    $groups=$d->fetchAll("SELECT id,name as fullname,avatar FROM `groups` WHERE name LIKE ? LIMIT 3",['%'.$q.'%']);
    foreach($groups as $g){$g['type']='group';$g['shipping_company']='';$data[]=$g;}
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
