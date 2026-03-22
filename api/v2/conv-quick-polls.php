<?php
// ShipperShop API v2 — Conversation Quick Polls
// Inline yes/no or A/B polls within conversations
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

function cqp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cqp_ok('OK',['polls'=>[]]);
    $key='quick_polls_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $polls=$row?json_decode($row['value'],true):[];
    // Enrich
    foreach($polls as &$p){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($p['created_by']??0)]);
        if($u) $p['creator_name']=$u['fullname'];
        $totalVotes=0;foreach($p['options']??[] as $o){$totalVotes+=count($o['voters']??[]);}
        $p['total_votes']=$totalVotes;
        $p['user_voted']=false;
        foreach($p['options']??[] as $o){if(in_array($uid,$o['voters']??[])){$p['user_voted']=true;break;}}
    }unset($p);
    cqp_ok('OK',['polls'=>$polls,'count'=>count($polls)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cqp_ok('Missing conversation_id');
    $key='quick_polls_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $polls=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $question=trim($input['question']??'');
        $options=$input['options']??['Co','Khong'];
        if(!$question) cqp_ok('Nhap cau hoi');
        $maxId=0;foreach($polls as $p){if(intval($p['id']??0)>$maxId)$maxId=intval($p['id']);}
        $pollOptions=[];
        foreach(array_slice($options,0,4) as $i=>$opt){$pollOptions[]=['id'=>$i,'text'=>trim($opt),'voters'=>[]];}
        array_unshift($polls,['id'=>$maxId+1,'question'=>$question,'options'=>$pollOptions,'created_by'=>$uid,'created_at'=>date('c')]);
        if(count($polls)>30) $polls=array_slice($polls,0,30);
    }

    if($action==='vote'){
        $pollId=intval($input['poll_id']??0);
        $optionId=intval($input['option_id']??0);
        foreach($polls as &$p){
            if(intval($p['id']??0)===$pollId){
                // Remove previous vote
                foreach($p['options'] as &$o){$o['voters']=array_values(array_filter($o['voters']??[],function($v) use($uid){return $v!==$uid;}));}unset($o);
                // Add vote
                if(isset($p['options'][$optionId])) $p['options'][$optionId]['voters'][]=$uid;
                break;
            }
        }unset($p);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($polls)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($polls))]);
    cqp_ok($action==='vote'?'Da bo phieu':'Da tao khao sat!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
