<?php
// ShipperShop API v2 — Delivery Notes
// Save per-address delivery notes: gate code, directions, preferences
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

function dn2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='delivery_notes_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];
    $search=trim($_GET['search']??'');
    if($search){
        $sl=mb_strtolower($search);
        $notes=array_values(array_filter($notes,function($n) use($sl){
            return mb_strpos(mb_strtolower($n['address']??''),$sl)!==false||mb_strpos(mb_strtolower($n['note']??''),$sl)!==false;
        }));
    }
    dn2_ok('OK',['notes'=>array_slice($notes,0,50),'count'=>count($notes)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $notes=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $address=trim($input['address']??'');
        $note=trim($input['note']??'');
        $tags=$input['tags']??[];
        if(!$address||!$note) dn2_ok('Nhap dia chi va ghi chu');
        $maxId=0;foreach($notes as $n){if(intval($n['id']??0)>$maxId)$maxId=intval($n['id']);}
        array_unshift($notes,['id'=>$maxId+1,'address'=>$address,'note'=>$note,'tags'=>$tags,'created_at'=>date('c'),'used_count'=>0]);
        if(count($notes)>200) $notes=array_slice($notes,0,200);
    }

    if($action==='delete'){
        $noteId=intval($input['note_id']??0);
        $notes=array_values(array_filter($notes,function($n) use($noteId){return intval($n['id']??0)!==$noteId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($notes)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($notes))]);
    dn2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
