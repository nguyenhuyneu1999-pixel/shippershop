<?php
// ShipperShop API v2 — Customer Contacts
// Save frequent customer contacts with address, phone, delivery preferences
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

function cc3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='contacts_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $contacts=$row?json_decode($row['value'],true):[];
    $search=trim($_GET['search']??'');
    if($search){$sl=mb_strtolower($search);$contacts=array_values(array_filter($contacts,function($c) use($sl){return mb_strpos(mb_strtolower($c['name']??''),$sl)!==false||mb_strpos(mb_strtolower($c['phone']??''),$sl)!==false||mb_strpos(mb_strtolower($c['address']??''),$sl)!==false;}));}
    $favorites=array_values(array_filter($contacts,function($c){return !empty($c['favorite']);}));
    cc3_ok('OK',['contacts'=>array_slice($contacts,0,50),'favorites'=>$favorites,'count'=>count($contacts)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $contacts=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $name=trim($input['name']??'');$phone=trim($input['phone']??'');$address=trim($input['address']??'');
        $district=trim($input['district']??'');$notes=trim($input['notes']??'');
        $preference=$input['preference']??'any'; // any, morning, afternoon, evening
        if(!$name||!$phone) cc3_ok('Nhap ten va SDT');
        $maxId=0;foreach($contacts as $c){if(intval($c['id']??0)>$maxId)$maxId=intval($c['id']);}
        $contacts[]=['id'=>$maxId+1,'name'=>$name,'phone'=>$phone,'address'=>$address,'district'=>$district,'notes'=>$notes,'preference'=>$preference,'favorite'=>false,'delivery_count'=>0,'created_at'=>date('c')];
        if(count($contacts)>300) cc3_ok('Toi da 300 lien he');
    }

    if($action==='favorite'){
        $cid=intval($input['contact_id']??0);
        foreach($contacts as &$c){if(intval($c['id']??0)===$cid) $c['favorite']=!($c['favorite']??false);}unset($c);
    }

    if($action==='delete'){
        $cid=intval($input['contact_id']??0);
        $contacts=array_values(array_filter($contacts,function($c) use($cid){return intval($c['id']??0)!==$cid;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($contacts)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($contacts))]);
    cc3_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
