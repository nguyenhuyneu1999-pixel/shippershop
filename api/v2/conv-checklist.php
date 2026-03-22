<?php
// ShipperShop API v2 — Conversation Checklist
// Shared to-do/checklist within conversations for delivery coordination
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

function ccl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) ccl_ok('OK',['items'=>[]]);
    $key='conv_checklist_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $items=$row?json_decode($row['value'],true):[];
    $completed=count(array_filter($items,function($i){return !empty($i['done']);}));
    ccl_ok('OK',['items'=>$items,'total'=>count($items),'completed'=>$completed,'progress'=>count($items)>0?round($completed/count($items)*100):0]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) ccl_ok('Missing conversation_id');
    $key='conv_checklist_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $items=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $text=trim($input['text']??'');
        if(!$text) ccl_ok('Nhap noi dung');
        $maxId=0;foreach($items as $i){if(intval($i['id']??0)>$maxId)$maxId=intval($i['id']);}
        $items[]=['id'=>$maxId+1,'text'=>$text,'done'=>false,'added_by'=>$uid,'created_at'=>date('c')];
        if(count($items)>50) ccl_ok('Toi da 50 muc');
    }

    if($action==='toggle'){
        $itemId=intval($input['item_id']??0);
        foreach($items as &$i){if(intval($i['id']??0)===$itemId){$i['done']=!($i['done']??false);$i['completed_by']=$uid;$i['completed_at']=date('c');}}unset($i);
    }

    if($action==='delete'){
        $itemId=intval($input['item_id']??0);
        $items=array_values(array_filter($items,function($i) use($itemId){return intval($i['id']??0)!==$itemId;}));
    }

    if($action==='clear_done'){
        $items=array_values(array_filter($items,function($i){return empty($i['done']);}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($items)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($items))]);
    ccl_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
