<?php
// ShipperShop API v2 — Schedule Templates
// Pre-built posting schedules (daily at 8am, weekdays, etc.)
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$TEMPLATES=[
    ['id'=>'morning','name'=>'Sang som','desc'=>'Dang luc 7:00 moi ngay','schedule'=>'07:00','frequency'=>'daily','icon'=>'🌅'],
    ['id'=>'lunch','name'=>'Gio trua','desc'=>'Dang luc 12:00','schedule'=>'12:00','frequency'=>'daily','icon'=>'☀️'],
    ['id'=>'evening','name'=>'Buoi toi','desc'=>'Dang luc 19:00','schedule'=>'19:00','frequency'=>'daily','icon'=>'🌙'],
    ['id'=>'rush_hour','name'=>'Gio cao diem','desc'=>'Dang 8:00 va 17:30','schedule'=>'08:00,17:30','frequency'=>'daily','icon'=>'⏰'],
    ['id'=>'weekday','name'=>'Ngay thuong','desc'=>'Dang T2-T6 luc 9:00','schedule'=>'09:00','frequency'=>'weekdays','icon'=>'📅'],
    ['id'=>'weekend','name'=>'Cuoi tuan','desc'=>'Dang T7-CN luc 10:00','schedule'=>'10:00','frequency'=>'weekends','icon'=>'🎉'],
    ['id'=>'three_daily','name'=>'3 lan/ngay','desc'=>'7:00, 12:00, 19:00','schedule'=>'07:00,12:00,19:00','frequency'=>'daily','icon'=>'🔄'],
    ['id'=>'optimal','name'=>'Thoi diem vang','desc'=>'Dua tren du lieu tuong tac cao nhat','schedule'=>'auto','frequency'=>'daily','icon'=>'✨'],
];

$d=db();$action=$_GET['action']??'';

function st_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

// List templates
if($_SERVER['REQUEST_METHOD']==='GET'){
    st_ok('OK',['templates'=>$TEMPLATES,'count'=>count($TEMPLATES)]);
}

// Apply template to user's schedule
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $templateId=$input['template_id']??'';
    $template=null;
    foreach($TEMPLATES as $t){if($t['id']===$templateId){$template=$t;break;}}
    if(!$template) st_ok('Template not found');

    $key='schedule_template_'.$uid;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($template),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($template)]);

    st_ok('Da ap dung: '.$template['name'],$template);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
