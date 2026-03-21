<?php
// ShipperShop API v2 — Admin IP Whitelist/Blacklist
// Manage allowed/blocked IPs for admin access
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

function ai_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ai_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') ai_fail('Admin only',403);

$key='admin_ip_rules';

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $rules=$row?json_decode($row['value'],true):['whitelist'=>[],'blacklist'=>[],'mode'=>'off'];
    $rules['current_ip']=$_SERVER['REMOTE_ADDR']??'';
    ai_ok('OK',$rules);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $rules=$row?json_decode($row['value'],true):['whitelist'=>[],'blacklist'=>[],'mode'=>'off'];

    if($action==='add_whitelist'){
        $ip=trim($input['ip']??'');
        if(!$ip) ai_fail('Nhap IP');
        if(!in_array($ip,$rules['whitelist'])) $rules['whitelist'][]=$ip;
    }elseif($action==='add_blacklist'){
        $ip=trim($input['ip']??'');
        if(!$ip) ai_fail('Nhap IP');
        if(!in_array($ip,$rules['blacklist'])) $rules['blacklist'][]=$ip;
    }elseif($action==='remove'){
        $ip=trim($input['ip']??'');
        $list=$input['list']??'whitelist';
        $rules[$list]=array_values(array_filter($rules[$list],function($i) use($ip){return $i!==$ip;}));
    }elseif($action==='set_mode'){
        $rules['mode']=$input['mode']??'off'; // off, whitelist, blacklist
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($rules),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($rules)]);

    ai_ok('Da cap nhat',$rules);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
