<?php
// ShipperShop API v2 — User Work History
// Shipper work experience log: companies worked, periods, areas
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

function wh_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// GET: user's work history
if($_SERVER['REQUEST_METHOD']==='GET'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId){$uid=optional_auth();$userId=$uid;}
    if(!$userId) wh_ok('OK',['history'=>[]]);
    $key='work_history_'.$userId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $history=$row?json_decode($row['value'],true):[];
    wh_ok('OK',['history'=>$history,'count'=>count($history)]);
}

// POST: manage work history
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'add';
    $key='work_history_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $history=$row?json_decode($row['value'],true):[];

    if($action==='add'){
        $company=trim($input['company']??'');
        $role=trim($input['role']??'Shipper');
        $area=trim($input['area']??'');
        $startDate=$input['start_date']??'';
        $endDate=$input['end_date']??'';
        $current=!empty($input['current']);
        if(!$company) wh_ok('Nhap ten cong ty');
        $maxId=0;foreach($history as $h){if(intval($h['id']??0)>$maxId)$maxId=intval($h['id']);}
        array_unshift($history,['id'=>$maxId+1,'company'=>$company,'role'=>$role,'area'=>$area,'start_date'=>$startDate,'end_date'=>$current?'':$endDate,'current'=>$current,'added_at'=>date('c')]);
        if(count($history)>20) wh_ok('Toi da 20 muc');
    }

    if($action==='delete'){
        $itemId=intval($input['item_id']??0);
        $history=array_values(array_filter($history,function($h) use($itemId){return intval($h['id']??0)!==$itemId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($history)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($history))]);
    wh_ok($action==='delete'?'Da xoa':'Da them!',['count'=>count($history)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
