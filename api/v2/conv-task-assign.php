<?php
// ShipperShop API v2 — Conversation Task Assign
// Assign delivery tasks to specific users within conversation
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

function cta_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cta_ok('OK',['tasks'=>[]]);
    $key='conv_tasks_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tasks=$row?json_decode($row['value'],true):[];
    foreach($tasks as &$t){
        $assigner=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($t['assigned_by']??0)]);
        $assignee=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($t['assigned_to']??0)]);
        if($assigner) $t['assigner_name']=$assigner['fullname'];
        if($assignee) $t['assignee_name']=$assignee['fullname'];
    }unset($t);
    $pending=count(array_filter($tasks,function($t){return ($t['status']??'')==='pending';}));
    $done=count(array_filter($tasks,function($t){return ($t['status']??'')==='done';}));
    cta_ok('OK',['tasks'=>$tasks,'count'=>count($tasks),'pending'=>$pending,'done'=>$done]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cta_ok('Missing conversation_id');
    $key='conv_tasks_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tasks=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='assign'){
        $task=trim($input['task']??'');
        $assignTo=intval($input['assigned_to']??0);
        $priority=$input['priority']??'normal';
        $deadline=$input['deadline']??'';
        if(!$task) cta_ok('Nhap cong viec');
        $maxId=0;foreach($tasks as $t){if(intval($t['id']??0)>$maxId)$maxId=intval($t['id']);}
        array_unshift($tasks,['id'=>$maxId+1,'task'=>$task,'assigned_by'=>$uid,'assigned_to'=>$assignTo,'priority'=>$priority,'deadline'=>$deadline,'status'=>'pending','created_at'=>date('c')]);
        if(count($tasks)>50) $tasks=array_slice($tasks,0,50);
    }

    if($action==='complete'){
        $taskId=intval($input['task_id']??0);
        foreach($tasks as &$t){if(intval($t['id']??0)===$taskId){$t['status']='done';$t['completed_at']=date('c');$t['completed_by']=$uid;}}unset($t);
    }

    if($action==='delete'){
        $taskId=intval($input['task_id']??0);
        $tasks=array_values(array_filter($tasks,function($t) use($taskId){return intval($t['id']??0)!==$taskId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($tasks)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($tasks))]);
    cta_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
