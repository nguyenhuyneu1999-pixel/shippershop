<?php
// ShipperShop API v2 — Message Reactions
// React to messages with emoji (like iMessage/Messenger)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';
$VALID=['like','love','haha','wow','sad','angry'];

function mr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function mr_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

// GET: reactions for a message
if($_SERVER['REQUEST_METHOD']==='GET'){
    $msgId=intval($_GET['message_id']??0);
    if(!$msgId) mr_fail('Missing message_id');
    // Store reactions in settings for simplicity (no extra table)
    $key='msg_reactions_'.$msgId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reactions=$row?json_decode($row['value'],true):[];
    // Count per type
    $breakdown=[];$total=0;$myReaction=null;
    foreach($reactions as $r){
        $type=$r['reaction']??'';
        if(!isset($breakdown[$type]))$breakdown[$type]=0;
        $breakdown[$type]++;$total++;
        if(intval($r['user_id'])===$uid) $myReaction=$type;
    }
    mr_ok('OK',['reactions'=>$reactions,'breakdown'=>$breakdown,'total'=>$total,'my_reaction'=>$myReaction]);
}

// POST: react to message
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $msgId=intval($input['message_id']??0);
    $reaction=trim($input['reaction']??'like');
    if(!$msgId) mr_fail('Missing message_id');
    if(!in_array($reaction,$VALID)) mr_fail('Invalid reaction');

    $key='msg_reactions_'.$msgId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $reactions=$row?json_decode($row['value'],true):[];

    // Find existing reaction by this user
    $found=false;
    foreach($reactions as $i=>$r){
        if(intval($r['user_id'])===$uid){
            if($r['reaction']===$reaction){
                // Remove
                array_splice($reactions,$i,1);
                $found=true;
                $msg='Đã bỏ';$reacted=false;
            }else{
                // Change
                $reactions[$i]['reaction']=$reaction;
                $found=true;
                $msg='Đã đổi';$reacted=true;
            }
            break;
        }
    }
    if(!$found){
        $reactions[]=['user_id'=>$uid,'reaction'=>$reaction,'created_at'=>date('c')];
        $msg='Đã bày tỏ!';$reacted=true;
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($reactions)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($reactions))]);

    mr_ok($msg,['reacted'=>$reacted??false,'reaction'=>$reacted??false?$reaction:null]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
