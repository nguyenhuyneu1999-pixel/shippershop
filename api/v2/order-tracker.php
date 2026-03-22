<?php
// ShipperShop API v2 — Order Tracker
// Track delivery orders with real-time status updates + ETA
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

function ot_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='orders_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $orders=$row?json_decode($row['value'],true):[];
    $stats=['total'=>count($orders),'delivered'=>0,'in_transit'=>0,'failed'=>0];
    foreach($orders as $o){
        if(($o['status']??'')==='delivered') $stats['delivered']++;
        elseif(in_array($o['status']??'',['picked_up','in_transit'])) $stats['in_transit']++;
        elseif(($o['status']??'')==='failed') $stats['failed']++;
    }
    $stats['success_rate']=$stats['total']>0?round(($stats['delivered']/$stats['total'])*100,1):0;
    ot_ok('OK',['orders'=>array_slice($orders,0,50),'stats'=>$stats]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $orders=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $trackingCode=strtoupper(substr(md5(time().rand()),0,8));
        $order=['id'=>$trackingCode,'recipient'=>trim($input['recipient']??''),'address'=>trim($input['address']??''),'phone'=>trim($input['phone']??''),'cod'=>intval($input['cod']??0),'company'=>trim($input['company']??''),'status'=>'picked_up','note'=>trim($input['note']??''),'created_at'=>date('c'),'history'=>[['status'=>'picked_up','time'=>date('c')]]];
        array_unshift($orders,$order);
        if(count($orders)>200) $orders=array_slice($orders,0,200);
    }

    if($action==='update'){
        $orderId=$input['order_id']??'';
        $newStatus=$input['status']??'';
        foreach($orders as &$o){
            if(($o['id']??'')===$orderId){
                $o['status']=$newStatus;
                $o['history'][]=[ 'status'=>$newStatus,'time'=>date('c'),'note'=>trim($input['note']??'')];
                break;
            }
        }unset($o);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($orders)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($orders))]);
    ot_ok($action==='update'?'Da cap nhat!':'Da tao don! Ma: '.($trackingCode??''));
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
