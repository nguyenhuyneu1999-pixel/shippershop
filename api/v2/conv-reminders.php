<?php
// ShipperShop API v2 — Conversation Reminders
// Set reminders to follow up on conversations
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

function cr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='conv_reminders_'.$uid;

// List reminders
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reminders=$row?json_decode($row['value'],true):[];
    // Enrich with conversation info
    foreach($reminders as &$r){
        if($cid=intval($r['conv_id']??0)){
            $other=$d->fetchOne("SELECT u.fullname,u.avatar FROM conversation_members cm JOIN users u ON cm.user_id=u.id WHERE cm.conversation_id=? AND cm.user_id!=? LIMIT 1",[$cid,$uid]);
            if($other) $r['other_user']=$other;
        }
        $r['is_due']=isset($r['remind_at'])&&strtotime($r['remind_at'])<=time();
    }unset($r);
    // Sort: due first
    usort($reminders,function($a,$b){return ($b['is_due']??false)-($a['is_due']??false);});
    cr_ok('OK',['reminders'=>$reminders,'count'=>count($reminders),'due'=>count(array_filter($reminders,function($r){return $r['is_due']??false;}))]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reminders=$row?json_decode($row['value'],true):[];

    // Add reminder
    if(!$action||$action==='add'){
        $convId=intval($input['conversation_id']??0);
        $note=trim($input['note']??'');
        $remindAt=$input['remind_at']??date('Y-m-d H:i:s',time()+3600);
        if(!$convId) cr_ok('Missing conversation_id');
        $maxId=0;foreach($reminders as $r){if(intval($r['id']??0)>$maxId)$maxId=intval($r['id']);}
        $reminders[]=['id'=>$maxId+1,'conv_id'=>$convId,'note'=>$note,'remind_at'=>$remindAt,'created_at'=>date('c'),'done'=>false];
        if(count($reminders)>50) cr_ok('Toi da 50 nhac nho');
    }

    // Mark done
    if($action==='done'){
        $remId=intval($input['reminder_id']??0);
        foreach($reminders as &$r){if(intval($r['id']??0)===$remId) $r['done']=true;}unset($r);
    }

    // Delete
    if($action==='delete'){
        $remId=intval($input['reminder_id']??0);
        $reminders=array_values(array_filter($reminders,function($r) use($remId){return intval($r['id']??0)!==$remId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($reminders)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($reminders))]);

    cr_ok($action==='delete'?'Da xoa':($action==='done'?'Da hoan thanh':'Da dat nhac nho!'));
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
