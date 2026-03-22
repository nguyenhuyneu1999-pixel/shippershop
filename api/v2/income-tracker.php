<?php
// ShipperShop API v2 — Income Tracker
// Track daily income from deliveries with detailed breakdown by company, COD, tips
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

function it3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='income_tracker_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];
    $today=date('Y-m-d');$weekStart=date('Y-m-d',strtotime('monday this week'));$monthStart=date('Y-m-01');
    $todayIncome=0;$weekIncome=0;$monthIncome=0;$todayDeliveries=0;$monthDeliveries=0;
    $byCompany=[];
    foreach($entries as $e){
        $amt=intval($e['amount']??0);$dels=intval($e['deliveries']??0);$comp=$e['company']??'';
        if(($e['date']??'')===$today){$todayIncome+=$amt;$todayDeliveries+=$dels;}
        if(($e['date']??'')>=$weekStart) $weekIncome+=$amt;
        if(($e['date']??'')>=$monthStart){$monthIncome+=$amt;$monthDeliveries+=$dels;}
        if($comp){$byCompany[$comp]=($byCompany[$comp]??0)+$amt;}
    }
    arsort($byCompany);
    $companyBreakdown=[];
    foreach(array_slice($byCompany,0,5,true) as $c=>$a){$companyBreakdown[]=['company'=>$c,'amount'=>$a];}
    $avgPerDelivery=$monthDeliveries>0?round($monthIncome/$monthDeliveries):0;

    it3_ok('OK',['entries'=>array_slice($entries,0,30),'stats'=>['today'=>$todayIncome,'week'=>$weekIncome,'month'=>$monthIncome,'today_deliveries'=>$todayDeliveries,'month_deliveries'=>$monthDeliveries,'avg_per_delivery'=>$avgPerDelivery],'by_company'=>$companyBreakdown,'count'=>count($entries)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $entries=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $amount=intval($input['amount']??0);$deliveries=intval($input['deliveries']??0);
        $company=trim($input['company']??'');$codAmount=intval($input['cod_amount']??0);
        $tipAmount=intval($input['tip_amount']??0);$note=trim($input['note']??'');
        if($amount<=0) it3_ok('Nhap so tien');
        $maxId=0;foreach($entries as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        array_unshift($entries,['id'=>$maxId+1,'date'=>date('Y-m-d'),'amount'=>$amount,'deliveries'=>$deliveries,'company'=>$company,'cod_amount'=>$codAmount,'tip_amount'=>$tipAmount,'note'=>$note,'created_at'=>date('c')]);
        if(count($entries)>365) $entries=array_slice($entries,0,365);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($entries)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($entries))]);
    it3_ok('Da ghi thu nhap!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
