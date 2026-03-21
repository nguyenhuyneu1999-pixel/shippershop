<?php
// ShipperShop API v2 — User Personal Notes
// Private notes/journal for shippers (delivery notes, addresses, reminders)
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

function un_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='user_notes_'.$uid;

// List notes
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];
    // Filter by category
    $cat=$_GET['category']??'';
    if($cat) $notes=array_values(array_filter($notes,function($n) use($cat){return ($n['category']??'')===$cat;}));
    // Search
    $q=trim($_GET['q']??'');
    if($q) $notes=array_values(array_filter($notes,function($n) use($q){return mb_stripos($n['title']??'',$q)!==false||mb_stripos($n['content']??'',$q)!==false;}));
    un_ok('OK',['notes'=>$notes,'count'=>count($notes)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];

    // Add note
    if(!$action||$action==='add'){
        $title=trim($input['title']??'');
        $content=trim($input['content']??'');
        $category=$input['category']??'general';
        $pinned=!empty($input['pinned']);
        if(!$title&&!$content) un_ok('Nhap tieu de hoac noi dung');
        $maxId=0;foreach($notes as $n){if(intval($n['id']??0)>$maxId)$maxId=intval($n['id']);}
        $note=['id'=>$maxId+1,'title'=>$title,'content'=>$content,'category'=>$category,'pinned'=>$pinned,'created_at'=>date('c'),'updated_at'=>date('c')];
        if($pinned) array_unshift($notes,$note); else $notes[]=$note;
        if(count($notes)>200) un_ok('Toi da 200 ghi chu');
    }

    // Update note
    if($action==='update'){
        $noteId=intval($input['note_id']??0);
        foreach($notes as &$n){
            if(intval($n['id']??0)===$noteId){
                if(isset($input['title'])) $n['title']=$input['title'];
                if(isset($input['content'])) $n['content']=$input['content'];
                if(isset($input['category'])) $n['category']=$input['category'];
                if(isset($input['pinned'])) $n['pinned']=!!$input['pinned'];
                $n['updated_at']=date('c');
            }
        }unset($n);
    }

    // Delete note
    if($action==='delete'){
        $noteId=intval($input['note_id']??0);
        $notes=array_values(array_filter($notes,function($n) use($noteId){return intval($n['id']??0)!==$noteId;}));
    }

    // Pin/unpin
    if($action==='pin'){
        $noteId=intval($input['note_id']??0);
        foreach($notes as &$n){
            if(intval($n['id']??0)===$noteId) $n['pinned']=!($n['pinned']??false);
        }unset($n);
        // Sort: pinned first
        usort($notes,function($a,$b){$ap=$a['pinned']??false;$bp=$b['pinned']??false;if($ap&&!$bp)return -1;if(!$ap&&$bp)return 1;return 0;});
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($notes)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($notes))]);

    un_ok($action==='delete'?'Da xoa':($action==='update'?'Da cap nhat':'Da luu!'),['count'=>count($notes)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
