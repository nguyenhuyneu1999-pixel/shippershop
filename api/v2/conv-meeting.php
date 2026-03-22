<?php
// ShipperShop API v2 — Conversation Meeting Scheduler
// Schedule meetups/handoffs within conversations
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

function cm3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: meetings for a conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cm3_ok('OK',['meetings'=>[]]);
    $key='conv_meetings_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $meetings=$row?json_decode($row['value'],true):[];
    // Enrich + filter
    foreach($meetings as &$m){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($m['created_by']??0)]);
        if($u){$m['creator_name']=$u['fullname'];$m['creator_avatar']=$u['avatar'];}
        $m['is_past']=isset($m['datetime'])&&strtotime($m['datetime'])<time();
        $m['is_today']=isset($m['datetime'])&&date('Y-m-d',strtotime($m['datetime']))===date('Y-m-d');
    }unset($m);
    // Sort: upcoming first
    usort($meetings,function($a,$b){return strcmp($a['datetime']??'',$b['datetime']??'');});
    $upcoming=array_values(array_filter($meetings,function($m){return !($m['is_past']??false);}));
    cm3_ok('OK',['meetings'=>$meetings,'upcoming'=>$upcoming,'count'=>count($meetings)]);
}

// POST: create/update/cancel meeting
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cm3_ok('Missing conversation_id');
    $key='conv_meetings_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $meetings=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $title=trim($input['title']??'');
        $datetime=$input['datetime']??'';
        $location=trim($input['location']??'');
        $note=trim($input['note']??'');
        if(!$title||!$datetime) cm3_ok('Nhap tieu de va thoi gian');
        $maxId=0;foreach($meetings as $m){if(intval($m['id']??0)>$maxId)$maxId=intval($m['id']);}
        $meetings[]=['id'=>$maxId+1,'title'=>$title,'datetime'=>$datetime,'location'=>$location,'note'=>$note,'created_by'=>$uid,'created_at'=>date('c'),'status'=>'scheduled'];
        if(count($meetings)>50) cm3_ok('Toi da 50 cuoc hen');
    }

    if($action==='cancel'){
        $meetId=intval($input['meeting_id']??0);
        foreach($meetings as &$m){if(intval($m['id']??0)===$meetId) $m['status']='cancelled';}unset($m);
    }

    if($action==='delete'){
        $meetId=intval($input['meeting_id']??0);
        $meetings=array_values(array_filter($meetings,function($m) use($meetId){return intval($m['id']??0)!==$meetId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($meetings)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($meetings))]);
    cm3_ok($action==='cancel'?'Da huy':($action==='delete'?'Da xoa':'Da tao cuoc hen!'));
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
