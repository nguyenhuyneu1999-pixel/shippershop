<?php
// ShipperShop API v2 — Announcements Scheduler
// Schedule announcements to appear at specific times
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

function as2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function as2_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: get active announcements
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='active')){
    $key='scheduled_announcements';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $all=$row?json_decode($row['value'],true):[];
    $active=array_values(array_filter($all,function($a){
        $now=time();
        $start=isset($a['start_at'])?strtotime($a['start_at']):0;
        $end=isset($a['end_at'])?strtotime($a['end_at']):PHP_INT_MAX;
        return $now>=$start&&$now<=$end&&!($a['dismissed']??false);
    }));
    as2_ok('OK',['announcements'=>$active,'count'=>count($active)]);
}

// Admin: list all
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='all'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') as2_fail('Admin only',403);
    $key='scheduled_announcements';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $all=$row?json_decode($row['value'],true):[];
    as2_ok('OK',['announcements'=>$all,'total'=>count($all)]);
}

// Admin: create scheduled announcement
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') as2_fail('Admin only',403);

    $input=json_decode(file_get_contents('php://input'),true);

    if(!$action||$action==='create'){
        $title=trim($input['title']??'');
        $message=trim($input['message']??'');
        $type=$input['type']??'info'; // info, warning, success, promo
        $startAt=$input['start_at']??date('c');
        $endAt=$input['end_at']??date('c',time()+86400);
        if(!$title) as2_fail('Nhap tieu de');

        $key='scheduled_announcements';
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $all=$row?json_decode($row['value'],true):[];
        $maxId=0;foreach($all as $a){if(intval($a['id']??0)>$maxId)$maxId=intval($a['id']);}
        $all[]=['id'=>$maxId+1,'title'=>$title,'message'=>$message,'type'=>$type,'start_at'=>$startAt,'end_at'=>$endAt,'created_by'=>$uid,'created_at'=>date('c')];

        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($all),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($all)]);
        as2_ok('Da tao thong bao!',['id'=>$maxId+1]);
    }

    if($action==='delete'){
        $annId=intval($input['id']??0);
        $key='scheduled_announcements';
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $all=$row?json_decode($row['value'],true):[];
        $all=array_values(array_filter($all,function($a) use($annId){return intval($a['id']??0)!==$annId;}));
        $d->query("UPDATE settings SET value=? WHERE `key`='scheduled_announcements'",[json_encode($all)]);
        as2_ok('Da xoa');
    }
}

as2_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
