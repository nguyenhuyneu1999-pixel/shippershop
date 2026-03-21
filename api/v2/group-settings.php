<?php
// ShipperShop API v2 — Group Notification Settings
// Per-group notification preferences, mute groups
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

function gs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function gs_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get notification setting for a group
    if($action==='get'){
        $gid=intval($_GET['group_id']??0);
        if(!$gid) gs_fail('Missing group_id');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['group_notif_'.$uid.'_'.$gid]);
        $prefs=$row?json_decode($row['value'],true):['posts'=>true,'comments'=>true,'members'=>true,'muted'=>false];
        gs_ok('OK',$prefs);
    }

    // List muted groups
    if($action==='muted'){
        $muted=$d->fetchAll("SELECT s.`key`,s.value,g.id,g.name,g.avatar FROM settings s JOIN `groups` g ON g.id=CAST(SUBSTRING_INDEX(s.`key`,'_',-1) AS UNSIGNED) WHERE s.`key` LIKE ? AND JSON_EXTRACT(s.value,'$.muted')=true",['group_notif_'.$uid.'_%']);
        $result=[];
        foreach($muted as $m){
            $result[]=['group_id'=>intval($m['id']),'name'=>$m['name'],'avatar'=>$m['avatar']];
        }
        gs_ok('OK',$result);
    }

    gs_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $gid=intval($input['group_id']??0);
    if(!$gid) gs_fail('Missing group_id');

    // Toggle mute
    if($action==='toggle_mute'){
        $key='group_notif_'.$uid.'_'.$gid;
        $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`=?",[$key]);
        $prefs=$row?json_decode($row['value'],true):['muted'=>false];
        $prefs['muted']=!($prefs['muted']??false);
        if($row){$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);}
        else{$d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);}
        gs_ok($prefs['muted']?'Đã tắt thông báo nhóm':'Đã bật thông báo nhóm',['muted'=>$prefs['muted']]);
    }

    // Update settings
    if(!$action||$action==='update'){
        $key='group_notif_'.$uid.'_'.$gid;
        $prefs=['posts'=>(bool)($input['posts']??true),'comments'=>(bool)($input['comments']??true),'members'=>(bool)($input['members']??true),'muted'=>(bool)($input['muted']??false)];
        $row=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($row){$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);}
        else{$d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);}
        gs_ok('Đã lưu cài đặt nhóm',$prefs);
    }

    gs_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
