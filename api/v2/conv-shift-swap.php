<?php
// ShipperShop API v2 — Conversation Shift Swap
// Request and manage shift swaps between shippers in a conversation
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

function css2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) css2_ok('OK',['swaps'=>[]]);
    $key='conv_shift_swap_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $swaps=$row?json_decode($row['value'],true):[];
    foreach($swaps as &$s){
        $from=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($s['from_id']??0)]);
        $to=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($s['to_id']??0)]);
        if($from) $s['from_name']=$from['fullname'];
        if($to) $s['to_name']=$to['fullname'];
    }unset($s);
    $pending=count(array_filter($swaps,function($s){return ($s['status']??'')==='pending';}));
    css2_ok('OK',['swaps'=>$swaps,'count'=>count($swaps),'pending'=>$pending]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) css2_ok('Missing');
    $key='conv_shift_swap_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $swaps=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='request'){
        $toId=intval($input['to_id']??0);
        $myShift=trim($input['my_shift']??'');
        $wantShift=trim($input['want_shift']??'');
        $date=$input['date']??date('Y-m-d');
        $reason=trim($input['reason']??'');
        if(!$toId||!$myShift) css2_ok('Nhap thong tin');
        $maxId=0;foreach($swaps as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $swaps[]=['id'=>$maxId+1,'from_id'=>$uid,'to_id'=>$toId,'my_shift'=>$myShift,'want_shift'=>$wantShift,'date'=>$date,'reason'=>$reason,'status'=>'pending','created_at'=>date('c')];
    }

    if($action==='accept'){
        $swapId=intval($input['swap_id']??0);
        foreach($swaps as &$s){if(intval($s['id']??0)===$swapId&&intval($s['to_id']??0)===$uid){$s['status']='accepted';$s['accepted_at']=date('c');}}unset($s);
    }

    if($action==='reject'){
        $swapId=intval($input['swap_id']??0);
        foreach($swaps as &$s){if(intval($s['id']??0)===$swapId){$s['status']='rejected';}}unset($s);
    }

    if(count($swaps)>50) $swaps=array_slice($swaps,-50);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($swaps)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($swaps))]);
    css2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
