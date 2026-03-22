<?php
// ShipperShop API v2 — Shift Planner
// Shippers plan weekly work shifts: morning/afternoon/evening per day
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$SHIFTS=[['id'=>'morning','name'=>'Sang (6-12h)','icon'=>'🌅'],['id'=>'afternoon','name'=>'Chieu (12-18h)','icon'=>'☀️'],['id'=>'evening','name'=>'Toi (18-24h)','icon'=>'🌙']];
$DAYS=['T2','T3','T4','T5','T6','T7','CN'];

function sp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='shift_plan_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $plan=$row?json_decode($row['value'],true):[];
    // Count total hours
    $totalShifts=0;foreach($plan as $day=>$shifts){$totalShifts+=count($shifts);}
    sp_ok('OK',['plan'=>$plan,'shifts'=>$SHIFTS,'days'=>$DAYS,'total_shifts'=>$totalShifts,'hours_per_week'=>$totalShifts*6]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'set';

    if($action==='set'){
        $plan=$input['plan']??[];
        // Validate: each day has array of shift ids
        $clean=[];
        foreach($DAYS as $day){
            if(isset($plan[$day])&&is_array($plan[$day])){
                $clean[$day]=array_values(array_intersect($plan[$day],array_column($SHIFTS,'id')));
            }
        }
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($clean),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($clean)]);
        sp_ok('Da luu lich lam viec!',['plan'=>$clean]);
    }

    if($action==='toggle'){
        $day=$input['day']??'';
        $shift=$input['shift']??'';
        if(!in_array($day,$DAYS)||!in_array($shift,array_column($SHIFTS,'id'))) sp_ok('Invalid');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $plan=$row?json_decode($row['value'],true):[];
        if(!isset($plan[$day])) $plan[$day]=[];
        $idx=array_search($shift,$plan[$day]);
        if($idx!==false) array_splice($plan[$day],$idx,1);
        else $plan[$day][]=$shift;
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($plan),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($plan)]);
        sp_ok('OK',['plan'=>$plan]);
    }
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
