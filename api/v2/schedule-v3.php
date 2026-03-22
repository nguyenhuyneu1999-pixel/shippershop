<?php
// ShipperShop API v2 — Post Schedule V3
// Advanced scheduling: recurring posts, optimal time auto-pick, draft queue
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

function sv3_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='schedule_v3_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedules=$row?json_decode($row['value'],true):[];
    $upcoming=array_values(array_filter($schedules,function($s){return ($s['status']??'')==='pending'&&strtotime($s['scheduled_at']??'')>time();}));
    $past=array_values(array_filter($schedules,function($s){return ($s['status']??'')!=='pending'||strtotime($s['scheduled_at']??'')<=time();}));
    usort($upcoming,function($a,$b){return strtotime($a['scheduled_at'])-strtotime($b['scheduled_at']);});
    // Optimal time suggestion
    $peakHour=$d->fetchOne("SELECT HOUR(created_at) as h FROM posts WHERE user_id=? AND `status`='active' GROUP BY HOUR(created_at) ORDER BY AVG(likes_count+comments_count) DESC LIMIT 1",[$uid]);
    $optimalHour=$peakHour?intval($peakHour['h']):20;
    sv3_ok('OK',['upcoming'=>$upcoming,'past'=>array_slice($past,0,10),'total'=>count($schedules),'optimal_hour'=>$optimalHour]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $schedules=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='add'){
        $content=trim($input['content']??'');
        $scheduledAt=$input['scheduled_at']??'';
        $recurring=$input['recurring']??'none'; // none, daily, weekly
        $autoOptimal=!empty($input['auto_optimal']);
        if(!$content) sv3_ok('Nhap noi dung');
        if($autoOptimal){
            $peakH=$d->fetchOne("SELECT HOUR(created_at) as h FROM posts WHERE user_id=? AND `status`='active' GROUP BY HOUR(created_at) ORDER BY AVG(likes_count+comments_count) DESC LIMIT 1",[$uid]);
            $h=$peakH?intval($peakH['h']):20;
            $scheduledAt=date('Y-m-d').' '.str_pad($h,2,'0',STR_PAD_LEFT).':00:00';
            if(strtotime($scheduledAt)<time()) $scheduledAt=date('Y-m-d',strtotime('+1 day')).' '.str_pad($h,2,'0',STR_PAD_LEFT).':00:00';
        }
        if(!$scheduledAt) sv3_ok('Chon thoi gian');
        $maxId=0;foreach($schedules as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $schedules[]=['id'=>$maxId+1,'content'=>$content,'scheduled_at'=>$scheduledAt,'recurring'=>$recurring,'status'=>'pending','created_at'=>date('c')];
        if(count($schedules)>100) sv3_ok('Toi da 100');
    }

    if($action==='cancel'){
        $schedId=intval($input['schedule_id']??0);
        foreach($schedules as &$s){if(intval($s['id']??0)===$schedId) $s['status']='cancelled';}unset($s);
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($schedules)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($schedules))]);
    sv3_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
