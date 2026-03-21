<?php
// ShipperShop API v2 — Content Queue Dashboard
// Overview of scheduled, draft, published, failed content
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

function qd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

// Dashboard overview
if(!$action||$action==='overview'){
    $scheduled=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE user_id=? AND `status`='scheduled'",[$uid])['c']);
    $drafts=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE user_id=? AND `status`='draft'",[$uid])['c']);
    $published=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE user_id=? AND `status`='published'",[$uid])['c']);
    $failed=intval($d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE user_id=? AND `status`='failed'",[$uid])['c']);

    // Next scheduled
    $next=$d->fetchOne("SELECT id,content,scheduled_at FROM content_queue WHERE user_id=? AND `status`='scheduled' AND scheduled_at > NOW() ORDER BY scheduled_at ASC LIMIT 1",[$uid]);

    // Recent activity
    $recent=$d->fetchAll("SELECT id,content,`status`,scheduled_at,created_at FROM content_queue WHERE user_id=? ORDER BY created_at DESC LIMIT 10",[$uid]);

    qd_ok('OK',[
        'counts'=>['scheduled'=>$scheduled,'drafts'=>$drafts,'published'=>$published,'failed'=>$failed,'total'=>$scheduled+$drafts+$published+$failed],
        'next_scheduled'=>$next,
        'recent'=>$recent,
    ]);
}

// List by status
if($action==='list'){
    $status=$_GET['status']??'scheduled';
    $limit=min(intval($_GET['limit']??20),50);
    $items=$d->fetchAll("SELECT * FROM content_queue WHERE user_id=? AND `status`=? ORDER BY COALESCE(scheduled_at,created_at) DESC LIMIT $limit",[$uid,$status]);
    qd_ok('OK',$items);
}

// Retry failed
if($action==='retry'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $itemId=intval($input['item_id']??0);
    if(!$itemId) qd_ok('Missing item_id');
    $item=$d->fetchOne("SELECT id FROM content_queue WHERE id=? AND user_id=? AND `status`='failed'",[$itemId,$uid]);
    if(!$item) qd_ok('Not found');
    $d->query("UPDATE content_queue SET `status`='scheduled',scheduled_at=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=?",[$itemId]);
    qd_ok('Đã đặt lại lịch');
}

qd_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
