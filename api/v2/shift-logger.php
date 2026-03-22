<?php
// ShipperShop API v2 — Shift Logger
// Clock in/out shifts, track hours worked, overtime, earnings per shift
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

function sl2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='shifts_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $shifts=$row?json_decode($row['value'],true):[];
    $active=null;$todayHours=0;$weekHours=0;$monthHours=0;
    $today=date('Y-m-d');$weekStart=date('Y-m-d',strtotime('monday this week'));$monthStart=date('Y-m-01');
    foreach($shifts as $s){
        if(empty($s['end'])){$active=$s;continue;}
        $hrs=round((strtotime($s['end'])-strtotime($s['start']))/3600,1);
        if(substr($s['start'],0,10)===$today) $todayHours+=$hrs;
        if(substr($s['start'],0,10)>=$weekStart) $weekHours+=$hrs;
        if(substr($s['start'],0,10)>=$monthStart) $monthHours+=$hrs;
    }
    sl2_ok('OK',['shifts'=>array_slice($shifts,0,30),'active'=>$active,'stats'=>['today'=>round($todayHours,1),'week'=>round($weekHours,1),'month'=>round($monthHours,1),'overtime'=>max(0,round($weekHours-48,1))]]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $shifts=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='clock_in'){
        foreach($shifts as $s){if(empty($s['end'])) sl2_ok('Dang co ca lam dang mo!');}
        $maxId=0;foreach($shifts as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        array_unshift($shifts,['id'=>$maxId+1,'start'=>date('c'),'end'=>null,'note'=>trim($input['note']??''),'deliveries'=>0]);
        if(count($shifts)>200) $shifts=array_slice($shifts,0,200);
    }

    if($action==='clock_out'){
        $deliveries=intval($input['deliveries']??0);
        $note=trim($input['note']??'');
        foreach($shifts as &$s){
            if(empty($s['end'])){
                $s['end']=date('c');
                $s['deliveries']=$deliveries;
                if($note) $s['end_note']=$note;
                $s['hours']=round((strtotime($s['end'])-strtotime($s['start']))/3600,1);
                break;
            }
        }unset($s);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($shifts)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($shifts))]);
    sl2_ok($action==='clock_out'?'Da ket thuc ca!':'Da bat dau ca lam!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
