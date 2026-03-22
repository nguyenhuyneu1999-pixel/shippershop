<?php
// ShipperShop API v2 — Conversation Expense Splitter
// Split shared expenses (fuel, tolls, parking) between shippers in a conversation
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

function es_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) es_ok('OK',['expenses'=>[]]);
    $key='expense_split_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $expenses=$row?json_decode($row['value'],true):[];

    // Enrich + calculate balances
    $balances=[];
    foreach($expenses as &$e){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($e['paid_by']??0)]);
        if($u) $e['payer_name']=$u['fullname'];
        $splitCount=max(1,count($e['split_with']??[])+1);
        $perPerson=round(intval($e['amount']??0)/$splitCount);
        $e['per_person']=$perPerson;

        // Track balances
        $payer=intval($e['paid_by']??0);
        $balances[$payer]=($balances[$payer]??0)+intval($e['amount']??0)-$perPerson;
        foreach($e['split_with']??[] as $sw){$balances[intval($sw)]=($balances[intval($sw)]??0)-$perPerson;}
    }unset($e);

    $totalExpenses=array_sum(array_column($expenses,'amount'));

    es_ok('OK',['expenses'=>$expenses,'balances'=>$balances,'total'=>$totalExpenses,'count'=>count($expenses)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) es_ok('Missing conversation_id');
    $key='expense_split_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $expenses=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $desc=trim($input['description']??'');
        $amount=intval($input['amount']??0);
        $splitWith=$input['split_with']??[];
        $category=$input['category']??'other';
        if(!$desc||$amount<=0) es_ok('Nhap mo ta va so tien');
        $maxId=0;foreach($expenses as $e){if(intval($e['id']??0)>$maxId)$maxId=intval($e['id']);}
        $expenses[]=['id'=>$maxId+1,'description'=>$desc,'amount'=>$amount,'category'=>$category,'paid_by'=>$uid,'split_with'=>array_map('intval',$splitWith),'created_at'=>date('c')];
        if(count($expenses)>100) es_ok('Toi da 100 chi phi');
    }

    if($action==='delete'){
        $expId=intval($input['expense_id']??0);
        $expenses=array_values(array_filter($expenses,function($e) use($expId){return intval($e['id']??0)!==$expId;}));
    }

    if($action==='settle'){$expenses=[];} // Clear all

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($expenses)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($expenses))]);
    es_ok($action==='settle'?'Da thanh toan het!':'OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
