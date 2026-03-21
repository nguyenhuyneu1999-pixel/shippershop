<?php
// ShipperShop API v2 — Admin Announcements
// System-wide banners, pinned announcements, maintenance notices
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function ann_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ann_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: get active announcements (no auth)
if($_SERVER['REQUEST_METHOD']==='GET'){
    if(!$action||$action==='active'){
        $anns=cache_remember('active_announcements', function() use($d) {
            $key='announcements';
            $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
            if(!$row) return [];
            $all=json_decode($row['value'],true);
            if(!is_array($all)) return [];
            $now=time();
            return array_values(array_filter($all,function($a) use($now){
                if(!($a['active']??true)) return false;
                if(isset($a['starts_at'])&&strtotime($a['starts_at'])>$now) return false;
                if(isset($a['ends_at'])&&strtotime($a['ends_at'])<$now) return false;
                return true;
            }));
        }, 60);
        ann_ok('OK',$anns);
    }

    // Admin: get all announcements
    if($action==='all'){
        $uid=require_auth();
        $user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$user||$user['role']!=='admin') ann_fail('Admin only',403);
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='announcements'");
        $all=$row?json_decode($row['value'],true):[];
        ann_ok('OK',$all);
    }
    ann_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$user||$user['role']!=='admin') ann_fail('Admin only',403);
    $input=json_decode(file_get_contents('php://input'),true);

    // Create/update announcement
    if(!$action||$action==='create'){
        $title=trim($input['title']??'');
        $message=trim($input['message']??'');
        $type=$input['type']??'info'; // info, warning, danger, success
        $startsAt=$input['starts_at']??null;
        $endsAt=$input['ends_at']??null;
        if(!$title||!$message) ann_fail('Title and message required');

        $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`='announcements'");
        $all=$row?json_decode($row['value'],true):[];
        if(!is_array($all)) $all=[];

        $ann=['id'=>'ann_'.time(),'title'=>$title,'message'=>$message,'type'=>$type,'active'=>true,'starts_at'=>$startsAt,'ends_at'=>$endsAt,'created_at'=>date('Y-m-d H:i:s'),'created_by'=>$uid];
        $all[]=$ann;

        if($row){$d->query("UPDATE settings SET value=? WHERE `key`='announcements'",[json_encode($all)]);}
        else{$d->query("INSERT INTO settings (`key`,value) VALUES ('announcements',?)",[json_encode($all)]);}

        cache_delete('active_announcements');
        ann_ok('Đã tạo thông báo',['id'=>$ann['id']]);
    }

    // Delete
    if($action==='delete'){
        $id=trim($input['id']??'');
        if(!$id) ann_fail('Missing id');
        $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`='announcements'");
        $all=$row?json_decode($row['value'],true):[];
        $all=array_values(array_filter($all,function($a) use($id){return ($a['id']??'')!==$id;}));
        $d->query("UPDATE settings SET value=? WHERE `key`='announcements'",[json_encode($all)]);
        cache_delete('active_announcements');
        ann_ok('Đã xóa');
    }

    // Toggle active
    if($action==='toggle'){
        $id=trim($input['id']??'');
        $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`='announcements'");
        $all=$row?json_decode($row['value'],true):[];
        foreach($all as &$a){if(($a['id']??'')===$id) $a['active']=!($a['active']??true);}unset($a);
        $d->query("UPDATE settings SET value=? WHERE `key`='announcements'",[json_encode($all)]);
        cache_delete('active_announcements');
        ann_ok('Đã cập nhật');
    }

    ann_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
