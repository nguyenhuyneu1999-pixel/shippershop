<?php
// ShipperShop API v2 — Expense Report
// Combined income + fuel + other expenses monthly report
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

function er2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$month=intval($_GET['month']??date('m'));
$year=intval($_GET['year']??date('Y'));
$startDate=sprintf('%04d-%02d-01',$year,$month);
$endDate=date('Y-m-t',strtotime($startDate)).' 23:59:59';

// Income
$incomeKey='income_'.$uid;
$iRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$incomeKey]);
$incomeEntries=$iRow?json_decode($iRow['value'],true):[];
$totalIncome=0;$deliveryCount=0;
foreach($incomeEntries as $e){
    if(isset($e['date'])&&$e['date']>=$startDate&&$e['date']<=$endDate){
        $totalIncome+=intval($e['amount']??0);
        $deliveryCount+=intval($e['deliveries']??0);
    }
}

// Fuel
$fuelKey='fuel_'.$uid;
$fRow=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$fuelKey]);
$fuelEntries=$fRow?json_decode($fRow['value'],true):[];
$totalFuel=0;$totalKm=0;
foreach($fuelEntries as $e){
    if(isset($e['date'])&&$e['date']>=$startDate&&$e['date']<=$endDate){
        $totalFuel+=intval($e['cost']??0);
        $totalKm+=floatval($e['km']??0);
    }
}

// Net profit
$netProfit=$totalIncome-$totalFuel;
$profitMargin=$totalIncome>0?round($netProfit/$totalIncome*100,1):0;
$avgPerDelivery=$deliveryCount>0?round($netProfit/$deliveryCount):0;
$costPerKm=$totalKm>0?round($totalFuel/$totalKm):0;

$months=['','Thang 1','Thang 2','Thang 3','Thang 4','Thang 5','Thang 6','Thang 7','Thang 8','Thang 9','Thang 10','Thang 11','Thang 12'];

er2_ok('OK',[
    'month'=>$month,'year'=>$year,'month_name'=>$months[$month].' '.$year,
    'income'=>$totalIncome,'fuel'=>$totalFuel,'net_profit'=>$netProfit,
    'profit_margin'=>$profitMargin,'deliveries'=>$deliveryCount,
    'avg_per_delivery'=>$avgPerDelivery,'total_km'=>$totalKm,'cost_per_km'=>$costPerKm,
]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
