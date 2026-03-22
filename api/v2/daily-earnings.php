<?php
// ShipperShop API v2 — Daily Earnings Report
// Detailed daily earnings breakdown: by hour, by company, COD vs shipping fee, tips
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

function de2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$date=$_GET['date']??date('Y-m-d');
$key='daily_earnings_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $allDays=$row?json_decode($row['value'],true):[];
    $today=$allDays[$date]??['entries'=>[],'total'=>0];
    $entries=$today['entries']??[];
    $totalEarning=array_sum(array_column($entries,'amount'));
    $totalCod=array_sum(array_column($entries,'cod'));
    $totalTips=array_sum(array_column($entries,'tip'));
    $totalFee=array_sum(array_column($entries,'fee'));
    $deliveryCount=count($entries);

    // By hour
    $byHour=[];
    foreach($entries as $e){$h=substr($e['time']??'00',0,2);$byHour[$h]=($byHour[$h]??0)+intval($e['amount']??0);}
    ksort($byHour);

    // By company
    $byCompany=[];
    foreach($entries as $e){$c=$e['company']??'other';$byCompany[$c]=($byCompany[$c]??0)+intval($e['amount']??0);}
    arsort($byCompany);

    // Hourly rate
    $firstEntry=$entries?$entries[count($entries)-1]:null;
    $lastEntry=$entries?$entries[0]:null;
    $hoursWorked=0;
    if($firstEntry&&$lastEntry){
        $start=strtotime($date.' '.($firstEntry['time']??'07:00'));
        $end=strtotime($date.' '.($lastEntry['time']??'21:00'));
        $hoursWorked=max(1,round(($end-$start)/3600,1));
    }
    $hourlyRate=$hoursWorked>0?round($totalEarning/$hoursWorked):0;

    de2_ok('OK',['date'=>$date,'entries'=>$entries,'stats'=>['total'=>$totalEarning,'cod'=>$totalCod,'tips'=>$totalTips,'fees'=>$totalFee,'deliveries'=>$deliveryCount,'hours_worked'=>$hoursWorked,'hourly_rate'=>$hourlyRate],'by_hour'=>$byHour,'by_company'=>$byCompany]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $allDays=$row?json_decode($row['value'],true):[];
    if(!isset($allDays[$date])) $allDays[$date]=['entries'=>[]];
    $amount=intval($input['amount']??0);
    $cod=intval($input['cod']??0);
    $tip=intval($input['tip']??0);
    $fee=intval($input['fee']??0);
    $company=trim($input['company']??'');
    $orderCode=trim($input['order_code']??'');
    if($amount<=0) de2_ok('Nhap so tien');
    $maxId=0;foreach($allDays[$date]['entries'] as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
    array_unshift($allDays[$date]['entries'],['id'=>$maxId+1,'time'=>date('H:i'),'amount'=>$amount,'cod'=>$cod,'tip'=>$tip,'fee'=>$fee,'company'=>$company,'order_code'=>$orderCode]);
    // Keep only last 30 days
    $keys=array_keys($allDays);sort($keys);
    while(count($keys)>30){$oldest=array_shift($keys);unset($allDays[$oldest]);}
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($allDays),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($allDays)]);
    de2_ok('Da ghi '.number_format($amount).'d!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
