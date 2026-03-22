<?php
// ShipperShop API v2 — Vehicle Manager
// Shippers manage their vehicles: type, plate, maintenance log
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';
$TYPES=[['id'=>'motorbike','name'=>'Xe may','icon'=>'🏍️'],['id'=>'ebike','name'=>'Xe dien','icon'=>'🔋'],['id'=>'car','name'=>'O to','icon'=>'🚗'],['id'=>'truck_s','name'=>'Xe tai nho','icon'=>'🚛'],['id'=>'truck_l','name'=>'Xe tai lon','icon'=>'🚚'],['id'=>'bicycle','name'=>'Xe dap','icon'=>'🚲']];

function vm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

if($action==='types'){vm_ok('OK',['types'=>$TYPES]);}
$uid=require_auth();
$key='vehicles_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    if($action==='types'){vm_ok('OK',['types'=>$TYPES]);}
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $vehicles=$row?json_decode($row['value'],true):[];
    vm_ok('OK',['vehicles'=>$vehicles,'count'=>count($vehicles),'types'=>$TYPES]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $vehicles=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $name=trim($input['name']??'');
        $type=$input['type']??'motorbike';
        $plate=trim($input['plate']??'');
        $year=intval($input['year']??date('Y'));
        if(!$name) vm_ok('Nhap ten xe');
        $maxId=0;foreach($vehicles as $v){if(intval($v['id']??0)>$maxId)$maxId=intval($v['id']);}
        $vehicles[]=['id'=>$maxId+1,'name'=>$name,'type'=>$type,'plate'=>$plate,'year'=>$year,'total_km'=>0,'maintenance'=>[],'created_at'=>date('c')];
        if(count($vehicles)>5) vm_ok('Toi da 5 xe');
    }

    if($action==='maintenance'){
        $vehicleId=intval($input['vehicle_id']??0);
        $desc=trim($input['description']??'');
        $cost=intval($input['cost']??0);
        foreach($vehicles as &$v){
            if(intval($v['id']??0)===$vehicleId){
                $v['maintenance'][]=[ 'desc'=>$desc,'cost'=>$cost,'date'=>date('Y-m-d')];
                if(count($v['maintenance'])>50) $v['maintenance']=array_slice($v['maintenance'],-50);
                break;
            }
        }unset($v);
    }

    if($action==='update_km'){
        $vehicleId=intval($input['vehicle_id']??0);
        $km=floatval($input['km']??0);
        foreach($vehicles as &$v){if(intval($v['id']??0)===$vehicleId) $v['total_km']=$km;}unset($v);
    }

    if($action==='delete'){
        $vehicleId=intval($input['vehicle_id']??0);
        $vehicles=array_values(array_filter($vehicles,function($v) use($vehicleId){return intval($v['id']??0)!==$vehicleId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($vehicles)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($vehicles))]);
    vm_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
