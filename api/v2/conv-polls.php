<?php
// ShipperShop API v2 — Conversation Polls
// Create polls inside conversations (like WhatsApp polls)
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

function cp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Get poll for a conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    $key='conv_poll_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $poll=$row?json_decode($row['value'],true):null;
    if($poll){
        // Check if user voted
        $votes=$poll['votes']??[];
        $poll['my_vote']=null;
        foreach($votes as $v){if(intval($v['user_id']??0)===$uid){$poll['my_vote']=$v['option'];break;}}
        $poll['total_votes']=count($votes);
    }
    cp_ok('OK',$poll);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Create poll
    if(!$action||$action==='create'){
        $convId=intval($input['conversation_id']??0);
        $question=trim($input['question']??'');
        $options=$input['options']??[];
        if(!$convId||!$question||count($options)<2) cp_ok('Can cau hoi va it nhat 2 lua chon');
        if(count($options)>10) cp_ok('Toi da 10 lua chon');

        $poll=['question'=>$question,'options'=>array_values(array_slice($options,0,10)),'votes'=>[],'created_by'=>$uid,'created_at'=>date('c'),'active'=>true];
        $key='conv_poll_'.$convId;
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($poll),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($poll)]);
        cp_ok('Da tao binh chon!');
    }

    // Vote
    if($action==='vote'){
        $convId=intval($input['conversation_id']??0);
        $option=intval($input['option']??-1);
        $key='conv_poll_'.$convId;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $poll=$row?json_decode($row['value'],true):null;
        if(!$poll||!($poll['active']??false)) cp_ok('Binh chon da dong');

        // Remove existing vote
        $poll['votes']=array_values(array_filter($poll['votes']??[],function($v) use($uid){return intval($v['user_id']??0)!==$uid;}));
        // Add new vote
        $poll['votes'][]=['user_id'=>$uid,'option'=>$option,'at'=>date('c')];

        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($poll),$key]);

        // Count per option
        $counts=[];
        foreach($poll['votes'] as $v){$o=intval($v['option']);$counts[$o]=($counts[$o]??0)+1;}
        cp_ok('Da binh chon!',['counts'=>$counts,'total'=>count($poll['votes'])]);
    }

    // Close poll
    if($action==='close'){
        $convId=intval($input['conversation_id']??0);
        $key='conv_poll_'.$convId;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $poll=$row?json_decode($row['value'],true):null;
        if($poll){$poll['active']=false;$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($poll),$key]);}
        cp_ok('Da dong binh chon');
    }
}

cp_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
