<?php
// ShipperShop API v2 — Conversation Expense Log
// Track shared expenses (fuel, meals, repairs) in delivery team conversations
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';
$CATEGORIES=[['id'=>'fuel','name'=>'Xang','icon'=>'⛽'],['id'=>'meal','name'=>'An uong','icon'=>'🍜'],['id'=>'repair','name'=>'Sua xe','icon'=>'🔧'],['id'=>'parking','name'=>'Gui xe','icon'=>'🅿️'],['id'=>'phone','name'=>'Dien thoai','icon'=>'📱'],['id'=>'other','name'=>'Khac','icon'=>'💰']];

function cel_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cel_ok('OK',['expenses'=>[],'categories'=>$CATEGORIES]);
    $key='conv_expenses_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $expenses=$row?json_decode($row['value'],true):[];
    foreach($expenses as &$e){$u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($e['user_id']??0)]);if($u)$e['user_name']=$u['fullname'];}unset($e);
    $total=array_sum(array_column($expenses,'amount'));
    $byCat=[];foreach($expenses as $e){$c=$e['category']??'other';$byCat[$c]=($byCat[$c]??0)+intval($e['amount']??0);}
    cel_ok('OK',['expenses'=>array_slice($expenses,0,30),'categories'=>$CATEGORIES,'total'=>$total,'by_category'=>$byCat,'count'=>count($expenses)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cel_ok('Missing conversation_id');
    $key='conv_expenses_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $expenses=$row?json_decode($row['value'],true):[];
    if(!$action||$action==='add'){
        $amount=intval($input['amount']??0);$desc=trim($input['description']??'');$cat=$input['category']??'other';
        if($amount<=0) cel_ok('Nhap so tien');
        $maxId=0;foreach($expenses as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        array_unshift($expenses,['id'=>$maxId+1,'amount'=>$amount,'description'=>$desc,'category'=>$cat,'user_id'=>$uid,'date'=>date('Y-m-d'),'created_at'=>date('c')]);
        if(count($expenses)>200) $expenses=array_slice($expenses,0,200);
    }
    if($action==='delete'){$eid=intval($input['expense_id']??0);$expenses=array_values(array_filter($expenses,function($e) use($eid){return intval($e['id']??0)!==$eid;}));}
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($expenses)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($expenses))]);
    cel_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
