<?php
// ShipperShop API v2 — Location Check-in
// Shippers check in at locations to show activity, earn XP
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ci_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function ci_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Recent check-ins for a location or user
    if($action==='nearby'||!$action){
        $province=$_GET['province']??'';
        $district=$_GET['district']??'';
        $limit=min(intval($_GET['limit']??20),50);
        $w=["1=1"];$p=[];
        if($province){$w[]="mp.province=?";$p[]=$province;}
        if($district){$w[]="mp.district=?";$p[]=$district;}
        $wc=implode(' AND ',$w);
        $pins=$d->fetchAll("SELECT mp.*,u.fullname,u.avatar,u.shipping_company FROM map_pins mp JOIN users u ON mp.user_id=u.id WHERE $wc ORDER BY mp.created_at DESC LIMIT $limit",$p);
        ci_ok('OK',$pins);
    }

    // User's check-in history
    if($action==='my'){
        $uid=require_auth();
        $pins=$d->fetchAll("SELECT * FROM map_pins WHERE user_id=? ORDER BY created_at DESC LIMIT 30",[$uid]);
        $totalCheckins=intval($d->fetchOne("SELECT COUNT(*) as c FROM map_pins WHERE user_id=?",[$uid])['c']);
        ci_ok('OK',['checkins'=>$pins,'total'=>$totalCheckins]);
    }

    // Leaderboard by check-ins
    if($action==='leaderboard'){
        $top=$d->fetchAll("SELECT mp.user_id,u.fullname,u.avatar,u.shipping_company,COUNT(*) as checkin_count FROM map_pins mp JOIN users u ON mp.user_id=u.id GROUP BY mp.user_id ORDER BY checkin_count DESC LIMIT 20");
        ci_ok('OK',$top);
    }

    ci_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    rate_enforce('checkin',10,3600);
    $input=json_decode(file_get_contents('php://input'),true);

    $lat=floatval($input['lat']??0);
    $lng=floatval($input['lng']??0);
    $note=trim($input['note']??'');
    $province=trim($input['province']??'');
    $district=trim($input['district']??'');

    if(!$lat||!$lng) ci_fail('Cần vị trí GPS');

    // Prevent duplicate check-in within 30 min
    $recent=$d->fetchOne("SELECT id FROM map_pins WHERE user_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",[$uid]);
    if($recent) ci_fail('Bạn đã check-in gần đây. Vui lòng đợi 30 phút');

    $pdo->prepare("INSERT INTO map_pins (user_id,lat,lng,note,province,district,created_at) VALUES (?,?,?,?,?,?,NOW())")->execute([$uid,$lat,$lng,$note,$province,$district]);
    $id=intval($pdo->lastInsertId());
    if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM map_pins");$id=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

    // Award XP
    try{$pdo->prepare("INSERT INTO user_xp (user_id,xp,reason,created_at) VALUES (?,5,'Checkin',NOW())")->execute([$uid]);}catch(\Throwable $e){}

    ci_ok('Đã check-in! +5 XP',['id'=>$id]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
