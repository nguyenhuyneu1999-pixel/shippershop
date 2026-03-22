<?php
// ShipperShop API v2 — Conversation Payment Split
// Split payment amounts between shippers with balance tracking
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

function cps2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cps2_ok('OK',['payments'=>[],'balances'=>[]]);
    $key='conv_payment_split_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $payments=$row?json_decode($row['value'],true):[];

    // Calculate balances
    $balances=[];
    foreach($payments as &$p){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($p['payer_id']??0)]);
        if($u) $p['payer_name']=$u['fullname'];
        $payerId=intval($p['payer_id']??0);
        $amount=intval($p['amount']??0);
        $splitCount=max(1,intval($p['split_count']??2));
        $share=round($amount/$splitCount);
        $balances[$payerId]=($balances[$payerId]??0)+$amount-$share;
        foreach($p['members']??[] as $memberId){
            $balances[intval($memberId)]=($balances[intval($memberId)]??0)-$share;
        }
    }unset($p);

    // Enrich balances with names
    $balanceList=[];
    foreach($balances as $userId=>$amount){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$userId]);
        $balanceList[]=['user_id'=>$userId,'name'=>$u?$u['fullname']:'User #'.$userId,'balance'=>$amount,'status'=>$amount>0?'owed':($amount<0?'owes':'settled')];
    }

    $totalAmount=array_sum(array_column($payments,'amount'));
    cps2_ok('OK',['payments'=>array_slice($payments,0,30),'balances'=>$balanceList,'total'=>$totalAmount,'count'=>count($payments)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cps2_ok('Missing conversation_id');
    $key='conv_payment_split_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $payments=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $desc=trim($input['description']??'');
        $amount=intval($input['amount']??0);
        $members=array_map('intval',$input['members']??[]);
        $splitCount=max(1,count($members)+1);
        if(!$desc||$amount<=0) cps2_ok('Nhap mo ta va so tien');
        $maxId=0;foreach($payments as $p){if(intval($p['id']??0)>$maxId)$maxId=intval($p['id']);}
        $payments[]=['id'=>$maxId+1,'description'=>$desc,'amount'=>$amount,'payer_id'=>$uid,'members'=>$members,'split_count'=>$splitCount,'created_at'=>date('c')];
        if(count($payments)>100) cps2_ok('Toi da 100');
    }

    if($action==='settle'){$payments=[];}

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($payments)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($payments))]);
    cps2_ok($action==='settle'?'Da thanh toan xong!':'OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
