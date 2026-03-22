<?php
// ShipperShop API v2 — Schedule Queue Dashboard
// View all queued/scheduled content with status management
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

function sq_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // User's scheduled content
    $scheduled=$d->fetchAll("SELECT id,content,`status`,scheduled_at,created_at FROM content_queue WHERE user_id=? AND `status` IN ('scheduled','draft','failed') ORDER BY CASE `status` WHEN 'scheduled' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END, scheduled_at ASC LIMIT 50",[$uid]);
    $counts=['scheduled'=>0,'draft'=>0,'published'=>0,'failed'=>0];
    foreach($scheduled as $s){$counts[$s['status']]=($counts[$s['status']]??0)+1;}
    // Also count published
    $counts['published']=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE user_id=? AND `status`='published'",[$uid])['c']);
    sq_ok('OK',['items'=>$scheduled,'counts'=>$counts]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $itemId=intval($input['item_id']??0);

    if($action==='cancel'){
        $d->query("UPDATE content_queue SET `status`='draft' WHERE id=? AND user_id=? AND `status`='scheduled'",[$itemId,$uid]);
        sq_ok('Da huy lich');
    }
    if($action==='reschedule'){
        $newTime=$input['scheduled_at']??'';
        if($newTime) $d->query("UPDATE content_queue SET scheduled_at=?,`status`='scheduled' WHERE id=? AND user_id=?",[$newTime,$itemId,$uid]);
        sq_ok('Da doi lich');
    }
    if($action==='retry'){
        $d->query("UPDATE content_queue SET `status`='scheduled',scheduled_at=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=? AND user_id=? AND `status`='failed'",[$itemId,$uid]);
        sq_ok('Se thu lai');
    }
    sq_ok('OK');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
