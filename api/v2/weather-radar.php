<?php
// ShipperShop API v2 — Weather Radar
// Tinh nang: Canh bao thoi tiet theo khu vuc cho shipper
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

$WEATHER_CONDITIONS=[
    ['id'=>'clear','name'=>'Troi quang','icon'=>'☀️','risk'=>0,'tip'=>'Thoi tiet ly tuong'],
    ['id'=>'cloudy','name'=>'Nhieu may','icon'=>'⛅','risk'=>1,'tip'=>'Tot, it nang'],
    ['id'=>'drizzle','name'=>'Mua phun','icon'=>'🌦️','risk'=>2,'tip'=>'Mang ao mua mong'],
    ['id'=>'rain','name'=>'Mua vua','icon'=>'🌧️','risk'=>3,'tip'=>'Ao mua, che hang, chay cham'],
    ['id'=>'heavy_rain','name'=>'Mua to','icon'=>'⛈️','risk'=>4,'tip'=>'Han che, tim noi tru'],
    ['id'=>'storm','name'=>'Giot bao','icon'=>'🌪️','risk'=>5,'tip'=>'KHONG DI! Tru an toan'],
    ['id'=>'fog','name'=>'Suong mu','icon'=>'🌫️','risk'=>3,'tip'=>'Bat den, chay cham, canh giac'],
    ['id'=>'extreme_heat','name'=>'Nong cuc','icon'=>'🔥','risk'=>3,'tip'=>'Uong nuoc, tranh nang 11-14h'],
];

function wr2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    if($action==='conditions') wr2_ok('OK',['conditions'=>$WEATHER_CONDITIONS]);
    $key='weather_radar';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reports=$row?json_decode($row['value'],true):[];
    // Filter last 6 hours
    $recent=array_values(array_filter($reports,function($r){return (time()-strtotime($r['created_at']??''))<21600;}));
    foreach($recent as &$r){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($r['user_id']??0)]);
        if($u) $r['reporter']=$u['fullname'];
        $cond=null;foreach($WEATHER_CONDITIONS as $wc){if($wc['id']===($r['condition']??''))$cond=$wc;}
        if($cond){$r['condition_name']=$cond['name'];$r['icon']=$cond['icon'];$r['risk']=$cond['risk'];$r['tip']=$cond['tip'];}
    }unset($r);
    // Group by district
    $byDistrict=[];
    foreach($recent as $r){$dist=$r['district']??'unknown';if(!isset($byDistrict[$dist]))$byDistrict[$dist]=[];$byDistrict[$dist][]=$r;}
    // Overall risk
    $maxRisk=0;foreach($recent as $r){if(intval($r['risk']??0)>$maxRisk)$maxRisk=intval($r['risk']);}
    $riskLevel=$maxRisk>=4?'critical':($maxRisk>=3?'high':($maxRisk>=2?'medium':'low'));
    wr2_ok('OK',['reports'=>$recent,'by_district'=>$byDistrict,'risk_level'=>$riskLevel,'max_risk'=>$maxRisk,'count'=>count($recent)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $key='weather_radar';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reports=$row?json_decode($row['value'],true):[];
    $condition=$input['condition']??'clear';$district=trim($input['district']??'');
    $lat=floatval($input['lat']??0);$lng=floatval($input['lng']??0);$note=trim($input['note']??'');
    if(!$district) wr2_ok('Nhap quan/huyen');
    $maxId=0;foreach($reports as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
    $reports[]=['id'=>$maxId+1,'condition'=>$condition,'district'=>$district,'lat'=>$lat,'lng'=>$lng,'note'=>$note,'user_id'=>$uid,'created_at'=>date('c')];
    // Keep last 500 only
    if(count($reports)>500) $reports=array_slice($reports,-500);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($reports)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($reports))]);
    wr2_ok('Da bao cao thoi tiet!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
