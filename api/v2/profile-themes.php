<?php
// ShipperShop API v2 — Profile Themes
// Custom profile page themes (colors, cover image, layout)
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

function pt_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

// Preset themes
$THEMES=[
    ['id'=>'default','name'=>'Mặc định','primary'=>'#7C3AED','bg'=>'#f0f2f5','card'=>'#fff'],
    ['id'=>'ocean','name'=>'Đại dương','primary'=>'#0ea5e9','bg'=>'#f0f9ff','card'=>'#fff'],
    ['id'=>'forest','name'=>'Rừng xanh','primary'=>'#16a34a','bg'=>'#f0fdf4','card'=>'#fff'],
    ['id'=>'sunset','name'=>'Hoàng hôn','primary'=>'#ea580c','bg'=>'#fff7ed','card'=>'#fff'],
    ['id'=>'cherry','name'=>'Anh đào','primary'=>'#db2777','bg'=>'#fdf2f8','card'=>'#fff'],
    ['id'=>'midnight','name'=>'Nửa đêm','primary'=>'#6366f1','bg'=>'#1e1b4b','card'=>'#312e81'],
    ['id'=>'gold','name'=>'Vàng kim','primary'=>'#d97706','bg'=>'#fffbeb','card'=>'#fff'],
    ['id'=>'shipper','name'=>'ShipperShop','primary'=>'#EE4D2D','bg'=>'#FFF3EF','card'=>'#fff'],
];

try {

$action=$_GET['action']??'';

// List preset themes
if($_SERVER['REQUEST_METHOD']==='GET'&&($action==='presets'||!$action)){
    pt_ok('OK',['presets'=>$THEMES]);
}

// Get user's theme
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='get'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId) pt_ok('OK',['theme'=>$THEMES[0]]);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['profile_theme_'.$userId]);
    $theme=$row?json_decode($row['value'],true):$THEMES[0];
    pt_ok('OK',['theme'=>$theme]);
}

// Set user's theme
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $themeId=$input['theme_id']??'';
    $custom=$input['custom']??null;

    $theme=null;
    if($themeId){
        foreach($THEMES as $t){if($t['id']===$themeId){$theme=$t;break;}}
    }
    if($custom&&is_array($custom)){
        $theme=['id'=>'custom','name'=>'Tùy chỉnh','primary'=>$custom['primary']??'#7C3AED','bg'=>$custom['bg']??'#f0f2f5','card'=>$custom['card']??'#fff','cover_url'=>$custom['cover_url']??''];
    }
    if(!$theme) $theme=$THEMES[0];

    $key='profile_theme_'.$uid;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($theme),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($theme)]);

    pt_ok('Đã đổi theme!',$theme);
}

pt_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
