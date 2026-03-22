<?php
// ShipperShop API v2 — User Privacy Settings
// Control who can see profile, posts, online status, activity
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

function pv_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$DEFAULTS=[
    'profile_visibility'=>'public',    // public, followers, private
    'show_online_status'=>true,
    'show_activity_log'=>true,
    'show_followers_list'=>true,
    'show_following_list'=>true,
    'show_badges'=>true,
    'show_reputation'=>true,
    'allow_messages_from'=>'everyone', // everyone, followers, nobody
    'allow_group_invites'=>true,
    'show_in_search'=>true,
    'show_read_receipts'=>true,
    'hide_last_seen'=>false,
];

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Get privacy settings
    if($action==='check'){
        // Check if user can view another user's profile
        $targetId=intval($_GET['user_id']??0);
        if(!$targetId) pv_ok('OK',['can_view'=>true]);
        $key='privacy_'.$targetId;
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $prefs=$row?array_merge($DEFAULTS,json_decode($row['value'],true)??[]):$DEFAULTS;

        $vis=$prefs['profile_visibility'];
        $canView=true;
        if($vis==='private'&&$targetId!==$uid){
            $canView=false;
        }elseif($vis==='followers'&&$targetId!==$uid){
            $isFollower=!!$d->fetchOne("SELECT id FROM follows WHERE follower_id=? AND following_id=?",[$uid,$targetId]);
            $canView=$isFollower;
        }
        pv_ok('OK',['can_view'=>$canView,'visibility'=>$vis]);
    }

    // Get own settings
    $key='privacy_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $prefs=$row?array_merge($DEFAULTS,json_decode($row['value'],true)??[]):$DEFAULTS;
    pv_ok('OK',$prefs);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $key='privacy_'.$uid;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $prefs=$row?array_merge($DEFAULTS,json_decode($row['value'],true)??[]):$DEFAULTS;

    // Update only allowed keys
    foreach(array_keys($DEFAULTS) as $k){
        if(isset($input[$k])) $prefs[$k]=$input[$k];
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($prefs),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($prefs)]);

    pv_ok('Đã lưu cài đặt riêng tư',$prefs);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
