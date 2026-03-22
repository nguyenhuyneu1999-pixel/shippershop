<?php
// ShipperShop API v2 — Conversation Pinned Topics
// Pin important discussion topics in group/1:1 conversations
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

function cpt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cpt_ok('OK',['topics'=>[]]);
    $key='pinned_topics_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $topics=$row?json_decode($row['value'],true):[];
    foreach($topics as &$t){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($t['pinned_by']??0)]);
        if($u) $t['pinner_name']=$u['fullname'];
    }unset($t);
    cpt_ok('OK',['topics'=>$topics,'count'=>count($topics)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cpt_ok('Missing conversation_id');
    $key='pinned_topics_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $topics=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='pin'){
        $title=trim($input['title']??'');
        $description=trim($input['description']??'');
        if(!$title) cpt_ok('Nhap tieu de');
        $maxId=0;foreach($topics as $t){if(intval($t['id']??0)>$maxId)$maxId=intval($t['id']);}
        $topics[]=['id'=>$maxId+1,'title'=>$title,'description'=>$description,'pinned_by'=>$uid,'pinned_at'=>date('c')];
        if(count($topics)>10) cpt_ok('Toi da 10 chu de ghim');
    }

    if($action==='unpin'){
        $topicId=intval($input['topic_id']??0);
        $topics=array_values(array_filter($topics,function($t) use($topicId){return intval($t['id']??0)!==$topicId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($topics)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($topics))]);
    cpt_ok($action==='unpin'?'Da go ghim':'Da ghim chu de!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
