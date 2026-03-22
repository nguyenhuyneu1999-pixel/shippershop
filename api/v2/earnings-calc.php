<?php
// ShipperShop API v2 — Earnings Calculator
// Calculate potential earnings based on deliveries, distance, company rates
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$RATES=[
    ['company'=>'GHTK','base'=>15000,'per_km'=>4000,'cod_fee'=>1.5,'icon'=>'🟢'],
    ['company'=>'GHN','base'=>16500,'per_km'=>3800,'cod_fee'=>1.2,'icon'=>'🟠'],
    ['company'=>'J&T','base'=>14000,'per_km'=>4200,'cod_fee'=>1.8,'icon'=>'🔴'],
    ['company'=>'SPX','base'=>15500,'per_km'=>3500,'cod_fee'=>1.0,'icon'=>'🟧'],
    ['company'=>'Viettel Post','base'=>13000,'per_km'=>4500,'cod_fee'=>1.5,'icon'=>'🔵'],
    ['company'=>'Ninja Van','base'=>16000,'per_km'=>3600,'cod_fee'=>1.3,'icon'=>'🔶'],
    ['company'=>'BEST','base'=>12500,'per_km'=>4800,'cod_fee'=>2.0,'icon'=>'🟡'],
    ['company'=>'Ahamove','base'=>18000,'per_km'=>5000,'cod_fee'=>0,'icon'=>'🟤'],
];

try {

$action=$_GET['action']??'';

if(!$action||$action==='rates'){
    echo json_encode(['success'=>true,'data'=>['rates'=>$RATES,'count'=>count($RATES)]],JSON_UNESCAPED_UNICODE);exit;
}

if($action==='calculate'){
    $deliveries=max(1,min(200,intval($_GET['deliveries']??10)));
    $avgKm=max(1,min(50,floatval($_GET['avg_km']??5)));
    $avgCod=max(0,intval($_GET['avg_cod']??200000));
    $hoursWorked=max(1,min(16,floatval($_GET['hours']??8)));
    $fuelCostPerKm=intval($_GET['fuel_cost']??1500);

    $results=[];
    foreach($RATES as $r){
        $deliveryIncome=$deliveries*($r['base']+$r['per_km']*$avgKm);
        $codIncome=$deliveries*$avgCod*$r['cod_fee']/100;
        $grossIncome=$deliveryIncome+$codIncome;
        $fuelCost=$deliveries*$avgKm*$fuelCostPerKm;
        $netIncome=$grossIncome-$fuelCost;
        $perHour=$hoursWorked>0?round($netIncome/$hoursWorked):0;
        $perDelivery=$deliveries>0?round($netIncome/$deliveries):0;

        $results[]=['company'=>$r['company'],'icon'=>$r['icon'],'gross'=>round($grossIncome),'fuel'=>round($fuelCost),'net'=>round($netIncome),'per_hour'=>$perHour,'per_delivery'=>$perDelivery];
    }
    usort($results,function($a,$b){return $b['net']-$a['net'];});

    echo json_encode(['success'=>true,'data'=>['results'=>$results,'input'=>['deliveries'=>$deliveries,'avg_km'=>$avgKm,'avg_cod'=>$avgCod,'hours'=>$hoursWorked,'fuel_cost'=>$fuelCostPerKm]]],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
