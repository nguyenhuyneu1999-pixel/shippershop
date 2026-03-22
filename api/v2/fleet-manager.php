<?php
// ShipperShop API v2 — Fleet Manager
// Manage team of shippers: assign areas, track performance, set quotas
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

function fm2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='fleet_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $fleet=$row?json_decode($row['value'],true):[];

    // Enrich with user data
    foreach($fleet as &$m){
        $u=$d->fetchOne("SELECT fullname,avatar,shipping_company,total_posts FROM users WHERE id=?",[$m['user_id']??0]);
        if($u){$m['fullname']=$u['fullname'];$m['avatar']=$u['avatar'];$m['company']=$u['shipping_company'];$m['posts']=$u['total_posts'];}
        // Recent activity
        $recent=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$m['user_id']??0])['c']);
        $m['active_7d']=$recent;
        $m['status']=$recent>0?'active':'inactive';
    }unset($m);

    $activeCount=count(array_filter($fleet,function($m){return ($m['status']??'')==='active';}));
    fm2_ok('OK',['members'=>$fleet,'count'=>count($fleet),'active'=>$activeCount]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $fleet=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $memberId=intval($input['user_id']??0);
        $area=trim($input['area']??'');
        $quota=intval($input['daily_quota']??10);
        $role=$input['role']??'shipper'; // shipper, lead, manager
        if(!$memberId) fm2_ok('Nhap user ID');
        // Check not duplicate
        foreach($fleet as $m){if(intval($m['user_id']??0)===$memberId) fm2_ok('Da co trong doi');}
        $fleet[]=['user_id'=>$memberId,'area'=>$area,'daily_quota'=>$quota,'role'=>$role,'joined_at'=>date('c'),'performance_score'=>0];
        if(count($fleet)>50) fm2_ok('Toi da 50 thanh vien');
    }

    if($action==='update'){
        $memberId=intval($input['user_id']??0);
        foreach($fleet as &$m){
            if(intval($m['user_id']??0)===$memberId){
                if(isset($input['area'])) $m['area']=trim($input['area']);
                if(isset($input['daily_quota'])) $m['daily_quota']=intval($input['daily_quota']);
                if(isset($input['role'])) $m['role']=$input['role'];
                break;
            }
        }unset($m);
    }

    if($action==='remove'){
        $memberId=intval($input['user_id']??0);
        $fleet=array_values(array_filter($fleet,function($m) use($memberId){return intval($m['user_id']??0)!==$memberId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($fleet)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($fleet))]);
    fm2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
