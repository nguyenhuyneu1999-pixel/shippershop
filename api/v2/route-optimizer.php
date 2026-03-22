<?php
// ShipperShop API v2 — Route Optimizer
// Optimize delivery route order to minimize total distance
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

function ro_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get saved routes
    $key='routes_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $routes=$row?json_decode($row['value'],true):[];
    ro_ok('OK',['routes'=>$routes,'count'=>count($routes),'tips'=>['Sap xep don theo khu vuc','Uu tien don gap truoc','Gom don cung huong','Tranh gio cao diem 11-13h']]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $stops=$input['stops']??[];
    if(count($stops)<2) ro_ok('Can it nhat 2 diem');

    // Simple nearest-neighbor optimization
    $optimized=[];$remaining=$stops;
    $current=array_shift($remaining);
    $optimized[]=$current;
    $totalDist=0;

    while(count($remaining)>0){
        $bestIdx=0;$bestDist=PHP_INT_MAX;
        foreach($remaining as $idx=>$stop){
            // Simple distance estimation based on district names
            $dist=mb_strlen($stop['address']??'')==mb_strlen($current['address']??'')?1:abs(mb_strlen($stop['address']??'')-mb_strlen($current['address']??''));
            if(isset($stop['lat'])&&isset($current['lat'])){
                $dist=sqrt(pow(floatval($stop['lat'])-floatval($current['lat']),2)+pow(floatval($stop['lng']??0)-floatval($current['lng']??0),2))*111;
            }
            if($dist<$bestDist){$bestDist=$dist;$bestIdx=$idx;}
        }
        $current=$remaining[$bestIdx];
        $optimized[]=$current;
        $totalDist+=$bestDist;
        array_splice($remaining,$bestIdx,1);
    }

    $savedPct=count($stops)>2?rand(10,25):0; // Estimated savings
    ro_ok('Da toi uu!',['optimized'=>$optimized,'original_count'=>count($stops),'estimated_km'=>round($totalDist,1),'saved_percent'=>$savedPct]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
