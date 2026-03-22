<?php
// ShipperShop API v2 — Weather Alerts for Shippers
// Weather-based delivery recommendations per province
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$WEATHER_DATA=[
    ['province'=>'TP Ho Chi Minh','condition'=>'Nang nong','temp'=>35,'humidity'=>70,'wind'=>15,'alert'=>'Uong nhieu nuoc, tranh giao 12-14h','severity'=>'medium','icon'=>'☀️'],
    ['province'=>'Ha Noi','condition'=>'Mua phun','temp'=>22,'humidity'=>85,'wind'=>20,'alert'=>'Mang ao mua, can than duong tron','severity'=>'high','icon'=>'🌧️'],
    ['province'=>'Da Nang','condition'=>'Gio lon','temp'=>28,'humidity'=>75,'wind'=>35,'alert'=>'Han che giao hang lon ngoai troi','severity'=>'high','icon'=>'💨'],
    ['province'=>'Can Tho','condition'=>'Ngap nuoc','temp'=>30,'humidity'=>90,'wind'=>10,'alert'=>'Mot so tuyen duong bi ngap','severity'=>'critical','icon'=>'🌊'],
    ['province'=>'Binh Duong','condition'=>'Nang','temp'=>33,'humidity'=>65,'wind'=>10,'alert'=>'Thoi tiet tot de giao hang','severity'=>'low','icon'=>'⛅'],
    ['province'=>'Dong Nai','condition'=>'Mua rao','temp'=>29,'humidity'=>80,'wind'=>12,'alert'=>'Co the mua bat cho, chuan bi ao mua','severity'=>'medium','icon'=>'🌦️'],
];

try {

$province=trim($_GET['province']??'');
$action=$_GET['action']??'';

if(!$action||$action==='current'){
    $result=$WEATHER_DATA;
    if($province){
        $result=array_values(array_filter($result,function($w) use($province){return mb_stripos($w['province'],$province)!==false;}));
    }
    echo json_encode(['success'=>true,'data'=>['alerts'=>$result,'count'=>count($result),'updated_at'=>date('c')]],JSON_UNESCAPED_UNICODE);
    exit;
}

// Delivery safety score by province
if($action==='safety'){
    $scores=[];
    foreach($WEATHER_DATA as $w){
        $score=100;
        if($w['severity']==='critical') $score=30;
        elseif($w['severity']==='high') $score=50;
        elseif($w['severity']==='medium') $score=75;
        $scores[]=['province'=>$w['province'],'score'=>$score,'condition'=>$w['condition'],'icon'=>$w['icon']];
    }
    usort($scores,function($a,$b){return $b['score']-$a['score'];});
    echo json_encode(['success'=>true,'data'=>$scores],JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
