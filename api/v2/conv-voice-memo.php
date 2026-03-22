<?php
// ShipperShop API v2 — Conversation Voice Memo
// Save text-based voice memo descriptions with duration and priority
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

function cvm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cvm_ok('OK',['memos'=>[]]);
    $key='conv_voice_memos_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $memos=$row?json_decode($row['value'],true):[];
    foreach($memos as &$m){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($m['user_id']??0)]);
        if($u){$m['user_name']=$u['fullname'];$m['avatar']=$u['avatar'];}
    }unset($m);
    $totalDuration=array_sum(array_column($memos,'duration'));
    cvm_ok('OK',['memos'=>$memos,'count'=>count($memos),'total_duration'=>$totalDuration]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cvm_ok('Missing conversation_id');
    $key='conv_voice_memos_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $memos=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $transcript=trim($input['transcript']??'');
        $duration=max(1,intval($input['duration']??5));
        $priority=$input['priority']??'normal';
        if(!$transcript) cvm_ok('Nhap noi dung');
        $maxId=0;foreach($memos as $m){if(intval($m['id']??0)>$maxId)$maxId=intval($m['id']);}
        array_unshift($memos,['id'=>$maxId+1,'transcript'=>$transcript,'duration'=>$duration,'priority'=>$priority,'user_id'=>$uid,'listened_by'=>[],'created_at'=>date('c')]);
        if(count($memos)>50) $memos=array_slice($memos,0,50);
    }

    if($action==='listen'){
        $memoId=intval($input['memo_id']??0);
        foreach($memos as &$m){
            if(intval($m['id']??0)===$memoId&&!in_array($uid,$m['listened_by']??[])){
                $m['listened_by'][]=$uid;
            }
        }unset($m);
    }

    if($action==='delete'){
        $memoId=intval($input['memo_id']??0);
        $memos=array_values(array_filter($memos,function($m) use($memoId){return intval($m['id']??0)!==$memoId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($memos)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($memos))]);
    cvm_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
