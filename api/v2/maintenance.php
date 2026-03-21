<?php
// ShipperShop API v2 — Maintenance Mode
// Admin toggle maintenance mode, show custom message to users
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$key='maintenance_mode';

// Public check (no auth)
if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $data=['active'=>false,'message'=>'','eta'=>''];
    if($row&&$row['value']){
        $parsed=json_decode($row['value'],true);
        if(is_array($parsed)) $data=array_merge($data,$parsed);
    }
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;
}

// Admin toggle
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        $uid=require_auth();
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin'){http_response_code(403);echo json_encode(['success'=>false,'message'=>'Admin only']);exit;}

        $input=json_decode(file_get_contents('php://input'),true);
        $active=!empty($input['active']);
        $message=trim($input['message']??'He thong dang bao tri. Vui long quay lai sau.');
        $eta=$input['eta']??'';

        $data=['active'=>$active,'message'=>$message,'eta'=>$eta,'set_by'=>$uid,'set_at'=>date('c')];

        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($data),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($data)]);

        echo json_encode(['success'=>true,'message'=>$active?'Bat bao tri':'Tat bao tri','data'=>$data],JSON_UNESCAPED_UNICODE);
    } catch (\Throwable $e) {
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
}
