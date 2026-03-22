<?php
// ShipperShop API v2 — Break Timer
// Tinh nang: Nhac nghi ngoi theo quy dinh lao dong, theo doi thoi gian lam viec
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

$BREAK_RULES=[
    ['after_hours'=>2,'break_min'=>10,'name'=>'Nghi ngan','icon'=>'☕'],
    ['after_hours'=>4,'break_min'=>30,'name'=>'Nghi trua','icon'=>'🍜'],
    ['after_hours'=>6,'break_min'=>15,'name'=>'Nghi chieu','icon'=>'🧃'],
    ['after_hours'=>8,'break_min'=>60,'name'=>'Ket ca','icon'=>'🏠'],
];

function bt2_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='break_timer_'.$uid.'_'.date('Y-m-d');

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $timer=$row?json_decode($row['value'],true):['start'=>null,'breaks'=>[],'total_work_min'=>0,'total_break_min'=>0];
    $isWorking=!empty($timer['start'])&&empty($timer['end']);
    $currentWorkMin=0;
    if($isWorking){$currentWorkMin=round((time()-strtotime($timer['start']))/60)-intval($timer['total_break_min']??0);}
    // Next break recommendation
    $nextBreak=null;
    foreach($BREAK_RULES as $rule){
        if($currentWorkMin>=$rule['after_hours']*60&&!in_array($rule['name'],array_column($timer['breaks'],'type'))){
            $nextBreak=$rule;break;
        }
    }
    bt2_ok('OK',['timer'=>$timer,'is_working'=>$isWorking,'current_work_min'=>$currentWorkMin,'next_break'=>$nextBreak,'break_rules'=>$BREAK_RULES,'date'=>date('Y-m-d')]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $timer=$row?json_decode($row['value'],true):['start'=>null,'breaks'=>[],'total_work_min'=>0,'total_break_min'=>0];

    if($action==='start'){
        $timer['start']=date('c');$timer['end']=null;
    }
    if($action==='stop'){
        $timer['end']=date('c');
        if($timer['start']) $timer['total_work_min']=round((strtotime($timer['end'])-strtotime($timer['start']))/60);
    }
    if($action==='break_start'){
        $timer['on_break']=true;$timer['break_started']=date('c');
    }
    if($action==='break_end'){
        $breakMin=$timer['break_started']?round((time()-strtotime($timer['break_started']))/60):0;
        $timer['breaks'][]=['type'=>$input['type']??'custom','duration'=>$breakMin,'ended_at'=>date('c')];
        $timer['total_break_min']=intval($timer['total_break_min']??0)+$breakMin;
        $timer['on_break']=false;$timer['break_started']=null;
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($timer),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($timer)]);
    bt2_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
