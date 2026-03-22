<?php
// ShipperShop API v2 — Conversation File Manager
// Upload tracking, file list, storage usage per conversation
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

function cf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// GET: files in conversation
if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cf_ok('OK',['files'=>[]]);

    $key='conv_files_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $files=$row?json_decode($row['value'],true):[];

    // Enrich with sender
    foreach($files as &$f){
        $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($f['uploaded_by']??0)]);
        if($u){$f['uploader_name']=$u['fullname'];$f['uploader_avatar']=$u['avatar'];}
    }unset($f);

    // Storage stats
    $totalSize=0;foreach($files as $f){$totalSize+=intval($f['size']??0);}
    $byType=[];foreach($files as $f){$ext=$f['type']??'other';$byType[$ext]=($byType[$ext]??0)+1;}

    cf_ok('OK',['files'=>$files,'count'=>count($files),'total_size_kb'=>round($totalSize/1024,1),'by_type'=>$byType]);
}

// POST: register file upload
if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    $filename=trim($input['filename']??'');
    $fileType=trim($input['type']??'other');
    $size=intval($input['size']??0);
    $url=trim($input['url']??'');
    if(!$convId||!$filename) cf_ok('Missing data');

    $key='conv_files_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $files=$row?json_decode($row['value'],true):[];
    $maxId=0;foreach($files as $f){if(intval($f['id']??0)>$maxId)$maxId=intval($f['id']);}
    $files[]=['id'=>$maxId+1,'filename'=>$filename,'type'=>$fileType,'size'=>$size,'url'=>$url,'uploaded_by'=>$uid,'uploaded_at'=>date('c')];
    if(count($files)>500) $files=array_slice($files,-500);

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($files),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($files)]);
    cf_ok('Da luu file!',['id'=>$maxId+1]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
