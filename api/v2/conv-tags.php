<?php
// ShipperShop API v2 — Conversation Tags
// Tag conversations with custom labels for organization
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function ct_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$PRESET_TAGS=[
    ['id'=>'important','name'=>'Quan trong','color'=>'#ef4444','icon'=>'🔴'],
    ['id'=>'work','name'=>'Cong viec','color'=>'#3b82f6','icon'=>'💼'],
    ['id'=>'delivery','name'=>'Giao hang','color'=>'#22c55e','icon'=>'📦'],
    ['id'=>'friend','name'=>'Ban be','color'=>'#a855f7','icon'=>'👥'],
    ['id'=>'family','name'=>'Gia dinh','color'=>'#f59e0b','icon'=>'🏠'],
    ['id'=>'spam','name'=>'Rac','color'=>'#6b7280','icon'=>'🗑️'],
];

try {

$uid=require_auth();
$key='conv_tags_'.$uid;

// GET: list tags for conversations
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $allTags=$row?json_decode($row['value'],true):[];

    if($convId){
        $convTags=$allTags[$convId]??[];
        ct_ok('OK',['tags'=>$convTags,'presets'=>$PRESET_TAGS]);
    }

    ct_ok('OK',['all_tags'=>$allTags,'presets'=>$PRESET_TAGS]);
}

// POST: set tags for a conversation
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $tagIds=$input['tags']??[];
    if(!$convId) ct_ok('Missing conversation_id');

    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $allTags=$row?json_decode($row['value'],true):[];

    if(empty($tagIds)){
        unset($allTags[$convId]);
    }else{
        $allTags[$convId]=array_values(array_unique($tagIds));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($allTags),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($allTags)]);

    ct_ok('Da cap nhat tag',['tags'=>$allTags[$convId]??[]]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
