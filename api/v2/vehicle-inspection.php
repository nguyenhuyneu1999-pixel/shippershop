<?php
// ShipperShop API v2 — Vehicle Inspection
// Monthly vehicle inspection log: brakes, tires, lights, engine, chain, mirrors
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

$CHECKS=[
    ['id'=>'brakes','name'=>'Phanh','icon'=>'🛑','category'=>'safety'],
    ['id'=>'tires','name'=>'Lop xe','icon'=>'⭕','category'=>'safety'],
    ['id'=>'lights','name'=>'Den','icon'=>'💡','category'=>'safety'],
    ['id'=>'horn','name'=>'Coi','icon'=>'📢','category'=>'safety'],
    ['id'=>'mirrors','name'=>'Guong','icon'=>'🪞','category'=>'safety'],
    ['id'=>'engine','name'=>'May','icon'=>'⚙️','category'=>'performance'],
    ['id'=>'chain','name'=>'Sen/xich','icon'=>'🔗','category'=>'performance'],
    ['id'=>'oil','name'=>'Dau nhot','icon'=>'🛢️','category'=>'performance'],
    ['id'=>'battery','name'=>'Binh ac quy','icon'=>'🔋','category'=>'electrical'],
    ['id'=>'signals','name'=>'Xi nhan','icon'=>'🔶','category'=>'electrical'],
    ['id'=>'body','name'=>'Than xe','icon'=>'🏍️','category'=>'exterior'],
    ['id'=>'seat','name'=>'Yen xe','icon'=>'💺','category'=>'exterior'],
];

function vi2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='vehicle_inspection_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $inspections=$row?json_decode($row['value'],true):[];
    $latest=$inspections[0]??null;
    $daysSince=$latest?max(0,intval((time()-strtotime($latest['date']??''))/(86400))):999;
    $needsInspection=$daysSince>=30;
    vi2_ok('OK',['inspections'=>array_slice($inspections,0,12),'checks'=>$CHECKS,'latest'=>$latest,'days_since'=>$daysSince,'needs_inspection'=>$needsInspection,'count'=>count($inspections)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $inspections=$row?json_decode($row['value'],true):[];
    $results=$input['results']??[];// {check_id: 'good'|'fair'|'bad'}
    $notes=trim($input['notes']??'');$odometer=intval($input['odometer']??0);
    $good=0;$fair=0;$bad=0;
    foreach($results as $cid=>$status){if($status==='good')$good++;elseif($status==='fair')$fair++;else $bad++;}
    $total=max(1,$good+$fair+$bad);
    $score=round(($good*100+$fair*60+$bad*20)/$total);
    $grade=$score>=85?'A':($score>=65?'B':($score>=45?'C':'D'));
    $maxId=0;foreach($inspections as $insp){if(intval($insp['id']??0)>$maxId)$maxId=intval($insp['id']);}
    array_unshift($inspections,['id'=>$maxId+1,'date'=>date('Y-m-d'),'results'=>$results,'notes'=>$notes,'odometer'=>$odometer,'score'=>$score,'grade'=>$grade,'good'=>$good,'fair'=>$fair,'bad'=>$bad,'created_at'=>date('c')]);
    if(count($inspections)>24) $inspections=array_slice($inspections,0,24);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($inspections),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($inspections)]);
    vi2_ok('Kiem tra xong! '.$grade.' ('.$score.'/100)');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
