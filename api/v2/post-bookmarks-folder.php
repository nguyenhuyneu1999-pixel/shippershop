<?php
// ShipperShop API v2 — Post Bookmark Folders
// Organize saved posts into custom folders
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

function pbf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='bookmark_folders_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $folders=$row?json_decode($row['value'],true):[];
    // Count posts per folder
    foreach($folders as &$f){$f['post_count']=count($f['post_ids']??[]);}unset($f);
    pbf_ok('OK',['folders'=>$folders,'count'=>count($folders)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $folders=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $name=trim($input['name']??'');
        $icon=$input['icon']??'📁';
        if(!$name) pbf_ok('Nhap ten thu muc');
        $maxId=0;foreach($folders as $f){if(intval($f['id']??0)>$maxId)$maxId=intval($f['id']);}
        $folders[]=['id'=>$maxId+1,'name'=>$name,'icon'=>$icon,'post_ids'=>[],'created_at'=>date('c')];
        if(count($folders)>20) pbf_ok('Toi da 20 thu muc');
    }

    if($action==='add_post'){
        $folderId=intval($input['folder_id']??0);
        $postId=intval($input['post_id']??0);
        foreach($folders as &$f){
            if(intval($f['id']??0)===$folderId){
                if(!in_array($postId,$f['post_ids']??[])) $f['post_ids'][]=$postId;
                break;
            }
        }unset($f);
    }

    if($action==='remove_post'){
        $folderId=intval($input['folder_id']??0);
        $postId=intval($input['post_id']??0);
        foreach($folders as &$f){
            if(intval($f['id']??0)===$folderId) $f['post_ids']=array_values(array_filter($f['post_ids']??[],function($id) use($postId){return $id!==$postId;}));
        }unset($f);
    }

    if($action==='delete'){
        $folderId=intval($input['folder_id']??0);
        $folders=array_values(array_filter($folders,function($f) use($folderId){return intval($f['id']??0)!==$folderId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($folders)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($folders))]);
    pbf_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
