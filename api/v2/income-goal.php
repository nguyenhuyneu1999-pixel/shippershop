<?php
// ShipperShop API v2 — Income Goal
// Set monthly income targets and track progress toward goal
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

function ig_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='income_goal_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $goal=$row?json_decode($row['value'],true):['monthly_target'=>5000000,'currency'=>'VND'];

    // Current income from income tracker
    $incKey='income_'.$uid;
    $iRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$incKey]);
    $incomeEntries=$iRow?json_decode($iRow['value'],true):[];
    $monthStart=date('Y-m-01');
    $monthIncome=0;$monthDeliveries=0;
    foreach($incomeEntries as $e){
        if(isset($e['date'])&&$e['date']>=$monthStart){
            $monthIncome+=intval($e['amount']??0);
            $monthDeliveries+=intval($e['deliveries']??0);
        }
    }

    // Fuel expenses this month
    $fuelKey='fuel_'.$uid;
    $fRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$fuelKey]);
    $fuelEntries=$fRow?json_decode($fRow['value'],true):[];
    $monthFuel=0;
    foreach($fuelEntries as $e){if(isset($e['date'])&&$e['date']>=$monthStart) $monthFuel+=intval($e['cost']??0);}

    $netIncome=$monthIncome-$monthFuel;
    $target=intval($goal['monthly_target']??5000000);
    $progress=$target>0?min(100,round($netIncome/$target*100)):0;
    $daysLeft=intval(date('t'))-intval(date('j'));
    $dailyNeeded=$daysLeft>0?round(($target-$netIncome)/$daysLeft):0;
    $onTrack=$progress>=(intval(date('j'))/intval(date('t'))*100);

    ig_ok('OK',['goal'=>$goal,'current'=>['income'=>$monthIncome,'fuel'=>$monthFuel,'net'=>$netIncome,'deliveries'=>$monthDeliveries],'progress'=>$progress,'days_left'=>$daysLeft,'daily_needed'=>max(0,$dailyNeeded),'on_track'=>$onTrack]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $goal=['monthly_target'=>max(100000,min(100000000,intval($input['monthly_target']??5000000))),'currency'=>'VND','updated_at'=>date('c')];
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($goal),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($goal)]);
    ig_ok('Da cap nhat muc tieu!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
