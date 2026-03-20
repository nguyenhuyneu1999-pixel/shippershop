<?php
/**
 * ShipperShop API v2 — Traffic Alerts
 * Thin v2 wrapper: delegates to original, adds map_data endpoint
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

// GET: Active alerts
if($_SERVER['REQUEST_METHOD']==='GET'&&(!$action||$action==='list')){
    $category=$_GET['category']??'';
    $severity=$_GET['severity']??'';
    $w=["expires_at > NOW() OR expires_at IS NULL"];$p=[];
    if($category){$w[]="category=?";$p[]=$category;}
    if($severity){$w[]="severity=?";$p[]=$severity;}
    $wc=implode(' AND ',$w);
    $alerts=$d->fetchAll("SELECT a.*,u.fullname as user_name,u.avatar as user_avatar,(SELECT COUNT(*) FROM traffic_confirms WHERE alert_id=a.id AND type='confirm') as confirms,(SELECT COUNT(*) FROM traffic_confirms WHERE alert_id=a.id AND type='deny') as denies,(SELECT COUNT(*) FROM traffic_comments WHERE alert_id=a.id) as comments_count FROM traffic_alerts a LEFT JOIN users u ON a.user_id=u.id WHERE $wc ORDER BY a.created_at DESC LIMIT 50",$p);
    ok('OK',$alerts);
}

// GET: Map data (for Leaflet markers)
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='map_data'){
    $alerts=$d->fetchAll("SELECT id,title,category,severity,latitude,longitude,created_at FROM traffic_alerts WHERE (expires_at > NOW() OR expires_at IS NULL) AND latitude IS NOT NULL AND longitude IS NOT NULL");
    ok('OK',$alerts);
}

// GET: Alert comments
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='comments'){
    $aid=intval($_GET['alert_id']??0);
    $comments=$d->fetchAll("SELECT c.*,u.fullname as user_name,u.avatar as user_avatar FROM traffic_comments c LEFT JOIN users u ON c.user_id=u.id WHERE c.alert_id=? ORDER BY c.created_at ASC",[$aid]);
    ok('OK',$comments);
}

// POST: Create alert, vote, comment → delegate to original
if($_SERVER['REQUEST_METHOD']==='POST'){
    // Pass through to original traffic.php
    require __DIR__.'/../traffic.php';
    exit;
}

ok('OK',[]);
