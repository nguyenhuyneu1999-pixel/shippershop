<?php
// ShipperShop API v2 — Route Planner
// Shippers save delivery routes with multiple stops
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

function rp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='routes_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $routes=$row?json_decode($row['value'],true):[];
    rp_ok('OK',['routes'=>$routes,'count'=>count($routes)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $routes=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='save'){
        $name=trim($input['name']??'');
        $stops=$input['stops']??[];
        if(!$name||count($stops)<2) rp_ok('Can ten va it nhat 2 diem dung');
        $maxId=0;foreach($routes as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
        $totalDist=0;
        foreach($stops as &$s){$s['address']=trim($s['address']??'');$s['note']=trim($s['note']??'');}unset($s);
        array_unshift($routes,['id'=>$maxId+1,'name'=>$name,'stops'=>array_slice($stops,0,20),'stop_count'=>count($stops),'created_at'=>date('c')]);
        if(count($routes)>30) $routes=array_slice($routes,0,30);
    }

    if($action==='delete'){
        $routeId=intval($input['route_id']??0);
        $routes=array_values(array_filter($routes,function($r) use($routeId){return intval($r['id']??0)!==$routeId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($routes)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($routes))]);
    rp_ok($action==='delete'?'Da xoa':'Da luu tuyen duong!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
