<?php
// ShipperShop API v2 — Announcement Banner
// Site-wide banners (maintenance, events, promotions)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: public, max-age=60');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function ab_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ab_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// GET: active banners (public, cached)
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='active')){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='announcement_banners'");
    $banners=$row?json_decode($row['value'],true):[];
    // Filter active
    $active=[];
    foreach($banners as $b){
        if(!$b['active']) continue;
        if($b['expires_at']&&strtotime($b['expires_at'])<time()) continue;
        $active[]=$b;
    }
    ab_ok('OK',$active);
}

// Admin: manage banners
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='all'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ab_fail('Admin only',403);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='announcement_banners'");
    $banners=$row?json_decode($row['value'],true):[];
    ab_ok('OK',$banners);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ab_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);

    // Create banner
    if(!$action||$action==='create'){
        $text=trim($input['text']??'');
        $type=$input['type']??'info'; // info, warning, success, danger, promo
        $link=$input['link']??'';
        $hours=intval($input['hours']??24);
        if(!$text) ab_fail('Nhập nội dung');

        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='announcement_banners'");
        $banners=$row?json_decode($row['value'],true):[];
        $banners[]=['id'=>count($banners)+1,'text'=>$text,'type'=>$type,'link'=>$link,'active'=>true,'created_at'=>date('c'),'expires_at'=>$hours>0?date('c',time()+$hours*3600):null,'created_by'=>$uid];

        $key='announcement_banners';
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($banners),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($banners)]);
        ab_ok('Đã tạo thông báo',['id'=>count($banners)]);
    }

    // Toggle/delete
    if($action==='toggle'||$action==='delete'){
        $bannerId=intval($input['banner_id']??0);
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='announcement_banners'");
        $banners=$row?json_decode($row['value'],true):[];
        foreach($banners as &$b){
            if(($b['id']??0)===$bannerId){
                if($action==='toggle') $b['active']=!$b['active'];
                else $b['active']=false; // soft delete
            }
        }unset($b);
        if($action==='delete') $banners=array_values(array_filter($banners,function($b) use($bannerId){return ($b['id']??0)!==$bannerId;}));
        $d->query("UPDATE settings SET value=? WHERE `key`='announcement_banners'",[json_encode($banners)]);
        ab_ok('OK');
    }

    ab_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
