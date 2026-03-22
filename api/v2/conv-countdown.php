<?php
// ShipperShop API v2 — Conversation Countdown Timer
// Create countdowns for delivery deadlines, meetup times, events
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

function ccd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) ccd_ok('OK',['countdowns'=>[]]);
    $key='conv_countdown_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $countdowns=$row?json_decode($row['value'],true):[];
    // Enrich + calculate remaining
    foreach($countdowns as &$cd){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($cd['created_by']??0)]);
        if($u) $cd['creator_name']=$u['fullname'];
        $target=strtotime($cd['target_time']??'');
        $cd['remaining_seconds']=$target?max(0,$target-time()):0;
        $cd['is_expired']=$cd['remaining_seconds']<=0;
        $h=floor($cd['remaining_seconds']/3600);
        $m=floor(($cd['remaining_seconds']%3600)/60);
        $cd['remaining_text']=$cd['is_expired']?'Het han':$h.'h '.$m.'p';
    }unset($cd);
    // Sort: active first
    usort($countdowns,function($a,$b){return intval($a['is_expired'])-intval($b['is_expired']);});
    ccd_ok('OK',['countdowns'=>$countdowns,'count'=>count($countdowns)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) ccd_ok('Missing conversation_id');
    $key='conv_countdown_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $countdowns=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $title=trim($input['title']??'');
        $targetTime=$input['target_time']??'';
        if(!$title||!$targetTime) ccd_ok('Nhap tieu de va thoi gian');
        $maxId=0;foreach($countdowns as $cd){if(intval($cd['id']??0)>$maxId)$maxId=intval($cd['id']);}
        $countdowns[]=['id'=>$maxId+1,'title'=>$title,'target_time'=>$targetTime,'created_by'=>$uid,'created_at'=>date('c')];
        if(count($countdowns)>20) ccd_ok('Toi da 20');
    }

    if($action==='delete'){
        $cdId=intval($input['countdown_id']??0);
        $countdowns=array_values(array_filter($countdowns,function($cd) use($cdId){return intval($cd['id']??0)!==$cdId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($countdowns)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($countdowns))]);
    ccd_ok($action==='delete'?'Da xoa':'Da tao dem nguoc!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
