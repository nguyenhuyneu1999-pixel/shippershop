<?php
// ShipperShop API v2 — Conversation Order Board
// Kanban-style order tracking board within conversations
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

$COLUMNS=[
    ['id'=>'pending','name'=>'Cho xu ly','icon'=>'📋','color'=>'#94a3b8'],
    ['id'=>'picked','name'=>'Da lay','icon'=>'📦','color'=>'#7c3aed'],
    ['id'=>'delivering','name'=>'Dang giao','icon'=>'🏍️','color'=>'#f59e0b'],
    ['id'=>'done','name'=>'Hoan thanh','icon'=>'✅','color'=>'#22c55e'],
    ['id'=>'failed','name'=>'That bai','icon'=>'❌','color'=>'#ef4444'],
];

function cob_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cob_ok('OK',['orders'=>[],'columns'=>$COLUMNS]);
    $key='conv_order_board_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $orders=$row?json_decode($row['value'],true):[];
    foreach($orders as &$o){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($o['assigned_to']??0)]);
        if($u) $o['assignee_name']=$u['fullname'];
    }unset($o);

    // Group by column
    $board=[];
    foreach($COLUMNS as $col){
        $items=array_values(array_filter($orders,function($o) use($col){return ($o['column']??'pending')===$col['id'];}));
        $board[]=['column'=>$col,'items'=>$items,'count'=>count($items)];
    }

    $totalOrders=count($orders);
    $doneCount=count(array_filter($orders,function($o){return ($o['column']??'')==='done';}));

    cob_ok('OK',['board'=>$board,'columns'=>$COLUMNS,'total'=>$totalOrders,'done'=>$doneCount,'completion'=>$totalOrders>0?round($doneCount/$totalOrders*100):0]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cob_ok('Missing conversation_id');
    $key='conv_order_board_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $orders=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $title=trim($input['title']??'');
        $recipient=trim($input['recipient']??'');
        $address=trim($input['address']??'');
        $cod=intval($input['cod']??0);
        $assignedTo=intval($input['assigned_to']??0);
        if(!$title) cob_ok('Nhap tieu de');
        $maxId=0;foreach($orders as $o){if(intval($o['id']??0)>$maxId)$maxId=intval($o['id']);}
        $orders[]=['id'=>$maxId+1,'title'=>$title,'recipient'=>$recipient,'address'=>$address,'cod'=>$cod,'assigned_to'=>$assignedTo,'column'=>'pending','created_by'=>$uid,'created_at'=>date('c')];
        if(count($orders)>100) cob_ok('Toi da 100');
    }

    if($action==='move'){
        $orderId=intval($input['order_id']??0);
        $toColumn=$input['to_column']??'pending';
        $validCols=array_column($COLUMNS,'id');
        if(!in_array($toColumn,$validCols)) cob_ok('Invalid column');
        foreach($orders as &$o){
            if(intval($o['id']??0)===$orderId){
                $o['column']=$toColumn;
                $o['moved_at']=date('c');
                $o['moved_by']=$uid;
                break;
            }
        }unset($o);
    }

    if($action==='delete'){
        $orderId=intval($input['order_id']??0);
        $orders=array_values(array_filter($orders,function($o) use($orderId){return intval($o['id']??0)!==$orderId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($orders)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($orders))]);
    cob_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
