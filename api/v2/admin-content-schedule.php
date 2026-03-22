<?php
// ShipperShop API v2 — Admin Content Schedule
// View/manage all scheduled content across platform
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

function acs_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function acs_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') acs_fail('Admin only',403);

// Overview
if(!$action||$action==='overview'){
    $scheduled=$d->fetchAll("SELECT cq.id,cq.content,cq.`status`,cq.scheduled_at,cq.created_at,u.fullname,u.avatar FROM content_queue cq JOIN users u ON cq.user_id=u.id WHERE cq.`status`='scheduled' AND cq.scheduled_at > NOW() ORDER BY cq.scheduled_at ASC LIMIT 50");
    $counts=['scheduled'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='scheduled' AND scheduled_at > NOW()")['c']),'draft'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='draft'")['c']),'published'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published'")['c']),'failed'=>intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='failed'")['c'])];
    acs_ok('OK',['scheduled'=>$scheduled,'counts'=>$counts]);
}

// Cancel scheduled item
if($action==='cancel'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $itemId=intval($input['item_id']??0);
    if(!$itemId) acs_fail('Missing item_id');
    $d->query("UPDATE content_queue SET `status`='draft' WHERE id=? AND `status`='scheduled'",[$itemId]);
    acs_ok('Da huy lich');
}

// Reschedule
if($action==='reschedule'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $itemId=intval($input['item_id']??0);
    $newTime=$input['scheduled_at']??'';
    if(!$itemId||!$newTime) acs_fail('Missing data');
    $d->query("UPDATE content_queue SET scheduled_at=? WHERE id=?",[$newTime,$itemId]);
    acs_ok('Da doi lich');
}

acs_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
