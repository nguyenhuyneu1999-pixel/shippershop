<?php
// ShipperShop API v2 — Conversation Bookmarks
// Bookmark important messages in conversations for quick reference
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

function cb2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {
$uid=require_auth();
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cb2_ok('OK',['bookmarks'=>[]]);
    $key='conv_bookmarks_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $bookmarks=$row?json_decode($row['value'],true):[];
    cb2_ok('OK',['bookmarks'=>$bookmarks,'count'=>count($bookmarks)]);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cb2_ok('Missing conversation_id');
    $key='conv_bookmarks_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $bookmarks=$row?json_decode($row['value'],true):[];
    if(!$action||$action==='add'){
        $text=trim($input['text']??'');$label=trim($input['label']??'');
        if(!$text) cb2_ok('Nhap noi dung');
        $maxId=0;foreach($bookmarks as $b){if(intval($b['id']??0)>$maxId)$maxId=intval($b['id']);}
        $bookmarks[]=['id'=>$maxId+1,'text'=>$text,'label'=>$label,'user_id'=>$uid,'created_at'=>date('c')];
        if(count($bookmarks)>30) $bookmarks=array_slice($bookmarks,-30);
    }
    if($action==='remove'){
        $bId=intval($input['bookmark_id']??0);
        $bookmarks=array_values(array_filter($bookmarks,function($b) use($bId){return intval($b['id']??0)!==$bId;}));
    }
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($bookmarks)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($bookmarks))]);
    cb2_ok('OK!');
}
} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
