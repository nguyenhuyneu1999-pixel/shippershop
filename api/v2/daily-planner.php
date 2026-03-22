<?php
// ShipperShop API v2 — Daily Planner
// Plan delivery day: time slots, areas, targets, break times, weather check
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

function dpl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$date=$_GET['date']??date('Y-m-d');
$key='daily_plan_'.$uid.'_'.$date;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $plan=$row?json_decode($row['value'],true):null;

    if(!$plan){
        // Generate default plan
        $plan=['date'=>$date,'slots'=>[
            ['id'=>1,'time'=>'07:00-09:00','area'=>'','target'=>5,'actual'=>0,'status'=>'pending','type'=>'delivery'],
            ['id'=>2,'time'=>'09:00-11:30','area'=>'','target'=>8,'actual'=>0,'status'=>'pending','type'=>'delivery'],
            ['id'=>3,'time'=>'11:30-13:00','area'=>'','target'=>0,'actual'=>0,'status'=>'pending','type'=>'break'],
            ['id'=>4,'time'=>'13:00-15:00','area'=>'','target'=>6,'actual'=>0,'status'=>'pending','type'=>'delivery'],
            ['id'=>5,'time'=>'15:00-17:30','area'=>'','target'=>8,'actual'=>0,'status'=>'pending','type'=>'delivery'],
            ['id'=>6,'time'=>'17:30-18:30','area'=>'','target'=>0,'actual'=>0,'status'=>'pending','type'=>'break'],
            ['id'=>7,'time'=>'18:30-21:00','area'=>'','target'=>5,'actual'=>0,'status'=>'pending','type'=>'delivery'],
        ],'total_target'=>32,'total_actual'=>0,'notes'=>'','weather'=>'sunny','created_at'=>date('c')];
    }

    // Actual from posts today
    $todayPosts=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND DATE(created_at)=?",[$uid,$date])['c']);
    $plan['total_actual']=$todayPosts;
    $completedSlots=count(array_filter($plan['slots'],function($s){return ($s['status']??'')==='done';}));
    $plan['progress']=$plan['total_target']>0?round($plan['total_actual']/$plan['total_target']*100):0;
    $plan['slots_completed']=$completedSlots;

    dpl_ok('OK',$plan);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $plan=$row?json_decode($row['value'],true):['date'=>$date,'slots'=>[],'total_target'=>0,'total_actual'=>0,'notes'=>'','weather'=>'sunny','created_at'=>date('c')];

    if(!$action||$action==='update_slot'){
        $slotId=intval($input['slot_id']??0);
        foreach($plan['slots'] as &$s){
            if(intval($s['id']??0)===$slotId){
                if(isset($input['area'])) $s['area']=trim($input['area']);
                if(isset($input['target'])) $s['target']=intval($input['target']);
                if(isset($input['actual'])) $s['actual']=intval($input['actual']);
                if(isset($input['status'])) $s['status']=$input['status'];
                break;
            }
        }unset($s);
        $plan['total_target']=array_sum(array_column($plan['slots'],'target'));
    }

    if($action==='set_weather') $plan['weather']=$input['weather']??'sunny';
    if($action==='set_notes') $plan['notes']=trim($input['notes']??'');

    if($action==='add_slot'){
        $maxId=0;foreach($plan['slots'] as $s){if(intval($s['id']??0)>$maxId)$maxId=intval($s['id']);}
        $plan['slots'][]=['id'=>$maxId+1,'time'=>trim($input['time']??''),'area'=>trim($input['area']??''),'target'=>intval($input['target']??5),'actual'=>0,'status'=>'pending','type'=>$input['type']??'delivery'];
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($plan),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($plan)]);
    dpl_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
