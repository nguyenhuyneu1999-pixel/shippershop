<?php
// ShipperShop API v2 — User Skill Tags
// Shippers tag their skills (areas, vehicle types, experience)
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

function sk_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$PRESET_TAGS=[
    ['cat'=>'vehicle','tags'=>['Xe may','Xe tai nho','Xe tai lon','Xe dap dien','O to']],
    ['cat'=>'area','tags'=>['Noi thanh','Ngoai thanh','Lien tinh','Quoc te']],
    ['cat'=>'type','tags'=>['Thu gom','Giao cuoi','Tra hang','COD','Hang lon','Hang dong lanh','Do an']],
    ['cat'=>'experience','tags'=>['<1 nam','1-3 nam','3-5 nam','5+ nam']],
    ['cat'=>'time','tags'=>['Ca sang','Ca chieu','Ca toi','Full-time','Part-time']],
];

try {

// GET: user's skills or preset list
if($_SERVER['REQUEST_METHOD']==='GET'){
    $userId=intval($_GET['user_id']??0);
    if(!$userId){
        sk_ok('OK',['presets'=>$PRESET_TAGS]);
    }
    $key='skill_tags_'.$userId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $tags=$row?json_decode($row['value'],true):[];
    sk_ok('OK',['tags'=>$tags,'presets'=>$PRESET_TAGS]);
}

// POST: save user's skills
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);
    $tags=$input['tags']??[];
    if(!is_array($tags)) $tags=[];
    // Max 20 tags
    $tags=array_slice($tags,0,20);
    $key='skill_tags_'.$uid;
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($tags),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($tags)]);
    sk_ok('Da luu ky nang!',['tags'=>$tags,'count'=>count($tags)]);
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
