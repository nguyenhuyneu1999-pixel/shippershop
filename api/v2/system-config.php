<?php
// ShipperShop API v2 — System Configuration (Admin)
// Site-wide settings: maintenance mode, registration, post limits, etc.
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

$DEFAULTS=[
    'site_name'=>'ShipperShop',
    'site_desc'=>'Cộng đồng shipper Việt Nam',
    'maintenance_mode'=>false,
    'maintenance_message'=>'Hệ thống đang bảo trì. Vui lòng quay lại sau.',
    'registration_open'=>true,
    'max_post_length'=>5000,
    'max_comment_length'=>2000,
    'max_images_per_post'=>10,
    'max_upload_size_mb'=>10,
    'auto_moderate'=>true,
    'auto_hide_report_threshold'=>3,
    'require_email_verification'=>false,
    'default_subscription_plan'=>1,
    'enable_stories'=>true,
    'enable_marketplace'=>true,
    'enable_traffic'=>true,
    'enable_polls'=>true,
    'enable_reactions'=>true,
    'enable_sse'=>true,
];

function sc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: get key settings (maintenance, feature flags)
if($_SERVER['REQUEST_METHOD']==='GET'&&($_GET['action']??'')==='public'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='system_config'");
    $config=$row?json_decode($row['value'],true):[];
    $merged=array_merge($DEFAULTS,$config);
    sc_ok('OK',[
        'maintenance'=>$merged['maintenance_mode'],
        'maintenance_message'=>$merged['maintenance_message'],
        'registration_open'=>$merged['registration_open'],
        'features'=>[
            'stories'=>$merged['enable_stories'],
            'marketplace'=>$merged['enable_marketplace'],
            'traffic'=>$merged['enable_traffic'],
            'polls'=>$merged['enable_polls'],
            'reactions'=>$merged['enable_reactions'],
            'sse'=>$merged['enable_sse'],
        ],
    ]);
}

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') sc_fail('Admin only',403);

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`='system_config'");
    $config=$row?json_decode($row['value'],true):[];
    sc_ok('OK',array_merge($DEFAULTS,$config));
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    if(!$input) sc_fail('No data');

    $row=$d->fetchOne("SELECT id,value FROM settings WHERE `key`='system_config'");
    $current=$row?json_decode($row['value'],true):[];

    foreach($input as $k=>$v){
        if(array_key_exists($k,$DEFAULTS)) $current[$k]=$v;
    }

    if($row){$d->query("UPDATE settings SET value=? WHERE `key`='system_config'",[json_encode($current)]);}
    else{$d->query("INSERT INTO settings (`key`,value) VALUES ('system_config',?)",[json_encode($current)]);}

    cache_delete('system_config');

    // Audit
    try{$pdo=db()->getConnection();$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'system_config',?,?,NOW())")->execute([$uid,'Config updated: '.implode(', ',array_keys($input)),$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

    sc_ok('Đã lưu cấu hình',array_merge($DEFAULTS,$current));
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
