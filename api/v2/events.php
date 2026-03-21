<?php
// ShipperShop API v2 — Event Log (Webhook-ready)
// Logs platform events for future webhook/integration use
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

function ev_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ev_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Log an event (internal use — called from other APIs)
if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='log'){
    $input=json_decode(file_get_contents('php://input'),true);
    $type=$input['type']??'';
    $payload=$input['payload']??[];
    if(!$type) ev_fail('Missing type');
    // Store in audit_log with type prefix
    $pdo=db()->getConnection();
    $pdo->prepare("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,?,?,?,NOW())")->execute([
        intval($input['user_id']??0),
        'event:'.$type,
        json_encode($payload,JSON_UNESCAPED_UNICODE),
        $_SERVER['REMOTE_ADDR']??''
    ]);
    ev_ok('Logged');
}

// Get recent events (admin)
if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();
    $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(!$admin||$admin['role']!=='admin') ev_fail('Admin only',403);

    $type=$_GET['type']??'';
    $limit=min(intval($_GET['limit']??50),200);

    $w=[];$p=[];
    if($type){$w[]="action LIKE ?";$p[]='event:'.$type.'%';}
    else{$w[]="action LIKE 'event:%'";}

    $wc=count($w)?implode(' AND ',$w):'1=1';
    $events=$d->fetchAll("SELECT al.*,u.fullname FROM audit_log al LEFT JOIN users u ON al.user_id=u.id WHERE $wc ORDER BY al.created_at DESC LIMIT $limit",$p);

    // Parse event types
    foreach($events as &$e){
        $e['event_type']=str_replace('event:','',$e['action']);
        $e['payload']=json_decode($e['detail'],true);
    }unset($e);

    // Event type stats
    $stats=$d->fetchAll("SELECT REPLACE(action,'event:','') as event_type,COUNT(*) as count FROM audit_log WHERE action LIKE 'event:%' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY action ORDER BY count DESC LIMIT 20");

    ev_ok('OK',['events'=>$events,'stats'=>$stats]);
}

ev_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
