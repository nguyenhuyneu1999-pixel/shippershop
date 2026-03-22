<?php
// ShipperShop API v2 — Content Scheduler V2
// Advanced scheduling: recurring posts, bulk schedule, optimal time auto-pick
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

function sv2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='scheduler_v2_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedules=$row?json_decode($row['value'],true):[];

    // Upcoming
    $upcoming=array_filter($schedules,function($s){return strtotime($s['next_run']??'')>time();});
    usort($upcoming,function($a,$b){return strcmp($a['next_run']??'',$b['next_run']??'');});

    // Optimal hours from post data
    $optHours=$d->fetchAll("SELECT HOUR(created_at) as h, AVG(likes_count+comments_count) as eng FROM posts WHERE `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY HOUR(created_at) ORDER BY eng DESC LIMIT 5");

    sv2_ok('OK',['schedules'=>$schedules,'upcoming'=>array_values($upcoming),'optimal_hours'=>$optHours,'count'=>count($schedules)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedules=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $content=trim($input['content']??'');
        $scheduleAt=$input['schedule_at']??'';
        $recurring=$input['recurring']??'none'; // none, daily, weekly
        if(!$content) sv2_ok('Nhap noi dung');

        $maxId=0;foreach($schedules as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $nextRun=$scheduleAt?:date('Y-m-d H:i:s',strtotime('+1 hour'));
        $schedules[]=['id'=>$maxId+1,'content'=>$content,'schedule_at'=>$nextRun,'next_run'=>$nextRun,'recurring'=>$recurring,'status'=>'active','created_at'=>date('c'),'runs'=>0];
        if(count($schedules)>30) sv2_ok('Toi da 30 lich');
    }

    if($action==='pause'||$action==='resume'){
        $schedId=intval($input['schedule_id']??0);
        foreach($schedules as &$s){if(intval($s['id']??0)===$schedId) $s['status']=$action==='pause'?'paused':'active';}unset($s);
    }

    if($action==='delete'){
        $schedId=intval($input['schedule_id']??0);
        $schedules=array_values(array_filter($schedules,function($s) use($schedId){return intval($s['id']??0)!==$schedId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($schedules)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($schedules))]);
    sv2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
