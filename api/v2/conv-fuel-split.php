<?php
// ShipperShop API v2 — Conversation Fuel Split
// Tinh nang: Chia tien xang khi di chung giua cac shipper
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

function cfs2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cfs2_ok('OK',['fills'=>[]]);
    $key='conv_fuel_split_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $fills=$row?json_decode($row['value'],true):[];
    foreach($fills as &$f){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($f['payer_id']??0)]);
        if($u) $f['payer_name']=$u['fullname'];
    }unset($f);
    $totalFuel=array_sum(array_column($fills,'amount'));
    $totalLiters=array_sum(array_column($fills,'liters'));
    // Balance per person
    $balances=[];
    foreach($fills as $f){
        $payerId=intval($f['payer_id']??0);
        $amount=intval($f['amount']??0);
        $riders=max(1,intval($f['riders']??2));
        $share=round($amount/$riders);
        $balances[$payerId]=($balances[$payerId]??0)+$amount-$share;
        foreach($f['rider_ids']??[] as $rid){$balances[intval($rid)]=($balances[intval($rid)]??0)-$share;}
    }
    $balanceList=[];
    foreach($balances as $userId=>$amt){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$userId]);
        $balanceList[]=['user_id'=>$userId,'name'=>$u?$u['fullname']:'#'.$userId,'balance'=>$amt];
    }
    cfs2_ok('OK',['fills'=>array_slice($fills,0,20),'balances'=>$balanceList,'total_fuel'=>$totalFuel,'total_liters'=>round($totalLiters,1),'count'=>count($fills)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cfs2_ok('Missing');
    $key='conv_fuel_split_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $fills=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $amount=intval($input['amount']??0);$liters=floatval($input['liters']??0);
        $riders=max(1,intval($input['riders']??2));$riderIds=array_map('intval',$input['rider_ids']??[]);
        $station=trim($input['station']??'');$note=trim($input['note']??'');
        if($amount<=0) cfs2_ok('Nhap so tien');
        $pricePerLiter=$liters>0?round($amount/$liters):0;
        $maxId=0;foreach($fills as $f){if(intval($f['id']??0)>$maxId)$maxId=intval($f['id']);}
        array_unshift($fills,['id'=>$maxId+1,'payer_id'=>$uid,'amount'=>$amount,'liters'=>$liters,'riders'=>$riders,'rider_ids'=>$riderIds,'station'=>$station,'price_per_liter'=>$pricePerLiter,'note'=>$note,'date'=>date('Y-m-d'),'created_at'=>date('c')]);
        if(count($fills)>100) $fills=array_slice($fills,0,100);
    }

    if($action==='settle'){$fills=[];}

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($fills)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($fills))]);
    cfs2_ok($action==='settle'?'Da thanh toan xong!':'Da ghi!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
