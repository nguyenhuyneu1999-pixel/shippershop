<?php
// ShipperShop API v2 — User Mute
// Hide posts from a user without blocking (they can still see your content)
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

function mu_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function mu_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List muted users
    if(!$action||$action==='list'){
        $key='muted_users_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $mutedIds=$row?json_decode($row['value'],true):[];
        if(!$mutedIds) mu_ok('OK',[]);
        $ph=implode(',',array_fill(0,count($mutedIds),'?'));
        $users=$d->fetchAll("SELECT id,fullname,avatar,shipping_company FROM users WHERE id IN ($ph)",$mutedIds);
        mu_ok('OK',$users);
    }

    // Check if muted
    if($action==='check'){
        $tid=intval($_GET['user_id']??0);
        if(!$tid) mu_ok('OK',['muted'=>false]);
        $key='muted_users_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $mutedIds=$row?json_decode($row['value'],true):[];
        mu_ok('OK',['muted'=>in_array($tid,$mutedIds)]);
    }

    // Get muted user IDs (for feed filter)
    if($action==='ids'){
        $key='muted_users_'.$uid;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        mu_ok('OK',['ids'=>$row?json_decode($row['value'],true):[]]);
    }

    mu_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Toggle mute
    if(!$action||$action==='toggle'){
        $tid=intval($input['user_id']??0);
        if(!$tid||$tid===$uid) mu_fail('Invalid user');
        $key='muted_users_'.$uid;
        $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`=?",[$key]);
        $mutedIds=$row?json_decode($row['value'],true):[];
        $wasMuted=in_array($tid,$mutedIds);

        if($wasMuted){
            $mutedIds=array_values(array_filter($mutedIds,function($id) use($tid){return $id!==$tid;}));
        }else{
            $mutedIds[]=$tid;
            $mutedIds=array_values(array_unique($mutedIds));
        }

        if($row){$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($mutedIds),$key]);}
        else{$d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($mutedIds)]);}

        mu_ok($wasMuted?'Đã bỏ ẩn':'Đã ẩn bài viết của người này',['muted'=>!$wasMuted]);
    }

    mu_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
