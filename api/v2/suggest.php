<?php
// ShipperShop API v2 — Search Suggestions
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$q=trim($_GET['q']??'');
if(mb_strlen($q)<1){echo json_encode(['success'=>true,'data'=>[]]);exit;}

$d=db();$data=[];$errors=[];

try{
    $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE `status`='active' AND fullname LIKE ? ORDER BY total_posts DESC LIMIT 5",['%'.$q.'%']);
    foreach($users as $u){$u['type']='user';$data[]=$u;}
}catch(\Throwable $e){$errors[]='users:'.$e->getMessage();}

try{
    $pdo=$d->getConnection();
    $stmt=$pdo->prepare("SELECT id,name as fullname,avatar FROM `groups` WHERE name LIKE ? LIMIT 3");
    $stmt->execute(['%'.$q.'%']);
    $groups=$stmt->fetchAll(\PDO::FETCH_ASSOC);
    foreach($groups as $g){$g['type']='group';$g['shipping_company']='';$data[]=$g;}
}catch(\Throwable $e){$errors[]='groups:'.$e->getMessage();}

if($errors){
    echo json_encode(['success'=>true,'data'=>$data,'_debug'=>$errors],JSON_UNESCAPED_UNICODE);
}else{
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);
}
