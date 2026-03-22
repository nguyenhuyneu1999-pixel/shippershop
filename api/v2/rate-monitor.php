<?php
// ShipperShop API v2 — Rate Limit Monitor (Admin)
// View rate limit status, top consumers, blocked IPs
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$action=$_GET['action']??'';

function rm_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function rm_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$admin||$admin['role']!=='admin') rm_fail('Admin only',403);

// Current rate limits
if(!$action||$action==='current'){
    $limits=$d->fetchAll("SELECT * FROM rate_limits WHERE expires_at > NOW() ORDER BY hits DESC LIMIT 50");
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM rate_limits WHERE expires_at > NOW()")['c']);
    rm_ok('OK',['limits'=>$limits,'active_count'=>$total]);
}

// Top API consumers (by login attempts)
if($action==='top_consumers'){
    $hours=min(intval($_GET['hours']??24),168);
    $top=$d->fetchAll("SELECT ip,COUNT(*) as attempts,MAX(created_at) as last_attempt FROM login_attempts WHERE created_at > DATE_SUB(NOW(), INTERVAL $hours HOUR) GROUP BY ip ORDER BY attempts DESC LIMIT 30");
    rm_ok('OK',$top);
}

// Audit log recent
if($action==='audit'){
    $limit=min(intval($_GET['limit']??50),200);
    $logs=$d->fetchAll("SELECT al.*,u.fullname FROM audit_log al LEFT JOIN users u ON al.user_id=u.id ORDER BY al.created_at DESC LIMIT $limit");
    rm_ok('OK',$logs);
}

// Clear rate limit for IP
if($action==='clear'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $ip=trim($input['ip']??'');
    $key=trim($input['key']??'');
    if($ip){
        $d->query("DELETE FROM rate_limits WHERE `key` LIKE ?",['%'.$ip.'%']);
        rm_ok('Cleared rate limits for '.$ip);
    }elseif($key){
        $d->query("DELETE FROM rate_limits WHERE `key`=?",[$key]);
        rm_ok('Cleared: '.$key);
    }
    rm_fail('Specify ip or key');
}

rm_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
