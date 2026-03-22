<?php
// ShipperShop API v2 — Profile Themes
// Custom profile header colors, bio layout, cover effects
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

$THEMES=[
    ['id'=>'default','name'=>'Mặc định','gradient'=>'linear-gradient(135deg,#7C3AED,#5B21B6)','text'=>'#fff'],
    ['id'=>'ocean','name'=>'Đại dương','gradient'=>'linear-gradient(135deg,#0ea5e9,#2563eb)','text'=>'#fff'],
    ['id'=>'sunset','name'=>'Hoàng hôn','gradient'=>'linear-gradient(135deg,#f97316,#ef4444)','text'=>'#fff'],
    ['id'=>'forest','name'=>'Rừng xanh','gradient'=>'linear-gradient(135deg,#22c55e,#16a34a)','text'=>'#fff'],
    ['id'=>'midnight','name'=>'Đêm','gradient'=>'linear-gradient(135deg,#1e1b4b,#312e81)','text'=>'#fff'],
    ['id'=>'rose','name'=>'Hồng','gradient'=>'linear-gradient(135deg,#ec4899,#db2777)','text'=>'#fff'],
    ['id'=>'gold','name'=>'Vàng kim','gradient'=>'linear-gradient(135deg,#f59e0b,#d97706)','text'=>'#fff'],
    ['id'=>'shipper','name'=>'Shipper','gradient'=>'linear-gradient(135deg,#EE4D2D,#d63031)','text'=>'#fff'],
    ['id'=>'minimal','name'=>'Tối giản','gradient'=>'#f8fafc','text'=>'#1e293b'],
    ['id'=>'dark','name'=>'Tối','gradient'=>'#0f172a','text'=>'#e2e8f0'],
];

function pt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// Get themes list
if($_SERVER['REQUEST_METHOD']==='GET'&&($_GET['action']??'')==='list'){
    pt_ok('OK',$THEMES);
}

// Get user's theme
if($_SERVER['REQUEST_METHOD']==='GET'){
    $tid=intval($_GET['user_id']??0);
    if(!$tid){$uid=optional_auth();$tid=$uid;}
    if(!$tid) pt_ok('OK',['theme'=>'default']);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['profile_theme_'.$tid]);
    $theme=$row?json_decode($row['value'],true):['id'=>'default'];
    // Merge with theme data
    foreach($THEMES as $t){if($t['id']===($theme['id']??'default')){$theme=array_merge($t,$theme);break;}}
    pt_ok('OK',$theme);
}

// Set theme
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $themeId=trim($input['theme_id']??'default');
    $customGradient=trim($input['custom_gradient']??'');

    $valid=false;
    foreach($THEMES as $t){if($t['id']===$themeId){$valid=true;break;}}
    if(!$valid&&!$customGradient) $themeId='default';

    $data=['id'=>$themeId];
    if($customGradient) $data['custom_gradient']=$customGradient;

    $key='profile_theme_'.$uid;
    $row=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($row){$d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($data),$key]);}
    else{$d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($data)]);}

    pt_ok('Đã cập nhật theme!',$data);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
