<?php
// ShipperShop API v2 — Conversation Shared Notes
// Collaborative notepad within conversations for delivery coordination
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

function csn_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) csn_ok('OK',['notes'=>[]]);
    $key='shared_notes_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];
    foreach($notes as &$n){
        $u=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[intval($n['author_id']??0)]);
        if($u) $n['author_name']=$u['fullname'];
    }unset($n);
    csn_ok('OK',['notes'=>$notes,'count'=>count($notes)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) csn_ok('Missing conversation_id');
    $key='shared_notes_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $title=trim($input['title']??'');
        $content=trim($input['content']??'');
        if(!$content) csn_ok('Nhap noi dung');
        $maxId=0;foreach($notes as $n){if(intval($n['id']??0)>$maxId)$maxId=intval($n['id']);}
        array_unshift($notes,['id'=>$maxId+1,'title'=>$title,'content'=>$content,'author_id'=>$uid,'created_at'=>date('c'),'updated_at'=>date('c')]);
        if(count($notes)>50) $notes=array_slice($notes,0,50);
    }

    if($action==='edit'){
        $noteId=intval($input['note_id']??0);
        $content=trim($input['content']??'');
        foreach($notes as &$n){if(intval($n['id']??0)===$noteId){$n['content']=$content;$n['updated_at']=date('c');$n['last_editor']=$uid;}}unset($n);
    }

    if($action==='delete'){
        $noteId=intval($input['note_id']??0);
        $notes=array_values(array_filter($notes,function($n) use($noteId){return intval($n['id']??0)!==$noteId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($notes)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($notes))]);
    csn_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
