<?php
// ShipperShop API v2 — Tip Calculator
// Calculate tip suggestions based on order value, distance, weather
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {

$orderValue=max(0,intval($_GET['order_value']??50000));
$distance=max(0.5,min(50,floatval($_GET['distance']??3)));
$weather=$_GET['weather']??'normal'; // normal, rain, hot, storm
$isRushHour=!empty($_GET['rush_hour']);
$isHoliday=!empty($_GET['holiday']);

// Base tip: 5-15% of order value
$basePct=0.05;
$tips=[];

// Weather multiplier
$weatherMult=['normal'=>1.0,'rain'=>1.5,'hot'=>1.2,'storm'=>2.0];
$mult=$weatherMult[$weather]??1.0;
if($isRushHour) $mult*=1.3;
if($isHoliday) $mult*=1.5;
if($distance>10) $mult*=1.2;

$levels=[
    ['label'=>'Binh thuong','pct'=>5,'icon'=>'😊'],
    ['label'=>'Tot','pct'=>10,'icon'=>'👍'],
    ['label'=>'Tuyet voi','pct'=>15,'icon'=>'🌟'],
    ['label'=>'Hao phong','pct'=>20,'icon'=>'💎'],
];

foreach($levels as $l){
    $adjustedPct=$l['pct']*$mult;
    $amount=round($orderValue*$adjustedPct/100/1000)*1000; // Round to 1000d
    $amount=max(5000,$amount); // Minimum 5k
    $tips[]=['label'=>$l['label'],'icon'=>$l['icon'],'percentage'=>round($adjustedPct,1),'amount'=>$amount];
}

// Quick tip amounts
$quickTips=[5000,10000,15000,20000,30000,50000];

echo json_encode(['success'=>true,'data'=>['tips'=>$tips,'quick_tips'=>$quickTips,'multiplier'=>round($mult,2),'factors'=>['weather'=>$weather,'rush_hour'=>$isRushHour,'holiday'=>$isHoliday,'distance'=>$distance]]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
