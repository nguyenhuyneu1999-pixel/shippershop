<?php
// ShipperShop API v2 — Post Collaboration
// Co-author posts, invite collaborators, shared drafts
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

function pc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// List my collaborations
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='my')){
    $key='collabs_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $collabs=$row?json_decode($row['value'],true):[];
    pc_ok('OK',['collaborations'=>$collabs,'count'=>count($collabs)]);
}

// Invite collaborator
if($_SERVER['REQUEST_METHOD']==='POST'&&(!$action||$action==='invite')){
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??$input['draft_id']??0);
    $inviteeId=intval($input['user_id']??0);
    $role=$input['role']??'editor'; // editor, reviewer
    if(!$postId||!$inviteeId) pc_ok('Missing data');

    // Store collaboration
    $key='collabs_'.$inviteeId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $collabs=$row?json_decode($row['value'],true):[];
    $collabs[]=['post_id'=>$postId,'invited_by'=>$uid,'role'=>$role,'status'=>'pending','created_at'=>date('c')];

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($collabs),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($collabs)]);

    pc_ok('Da moi cong tac!');
}

// Accept/decline collaboration
if($_SERVER['REQUEST_METHOD']==='POST'&&($action==='accept'||$action==='decline')){
    $input=json_decode(file_get_contents('php://input'),true);
    $postId=intval($input['post_id']??0);
    $key='collabs_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $collabs=$row?json_decode($row['value'],true):[];
    foreach($collabs as &$c){
        if(intval($c['post_id']??0)===$postId&&($c['status']??'')==='pending'){
            $c['status']=$action==='accept'?'accepted':'declined';
            $c['resolved_at']=date('c');
        }
    }unset($c);
    $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($collabs),$key]);
    pc_ok($action==='accept'?'Da chap nhan':'Da tu choi');
}

pc_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
