<?php
// ShipperShop API v2 — Conversation Group Delivery
// Coordinate group deliveries: assign areas, track completion, share progress
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

function cgd_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $convId=intval($_GET['conversation_id']??0);
    if(!$convId) cgd_ok('OK',['missions'=>[]]);
    $key='conv_group_del_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $missions=$row?json_decode($row['value'],true):[];
    foreach($missions as &$m){
        foreach($m['assignments']??[] as &$a){
            $u=$d->fetchOne("SELECT fullname,avatar FROM users WHERE id=?",[intval($a['user_id']??0)]);
            if($u){$a['fullname']=$u['fullname'];$a['avatar']=$u['avatar'];}
        }unset($a);
        $totalOrders=array_sum(array_column($m['assignments']??[],'order_count'));
        $doneOrders=array_sum(array_column($m['assignments']??[],'completed'));
        $m['total_orders']=$totalOrders;$m['done_orders']=$doneOrders;
        $m['progress']=$totalOrders>0?round($doneOrders/$totalOrders*100):0;
    }unset($m);
    $activeMissions=array_values(array_filter($missions,function($m){return ($m['status']??'')==='active';}));
    cgd_ok('OK',['missions'=>$missions,'active'=>$activeMissions,'count'=>count($missions)]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $convId=intval($input['conversation_id']??0);
    if(!$convId) cgd_ok('Missing conversation_id');
    $key='conv_group_del_'.$convId;
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $missions=$row?json_decode($row['value'],true):[];

    if(!$action||$action==='create'){
        $title=trim($input['title']??'');
        $assignments=$input['assignments']??[];// [{user_id, area, order_count}]
        if(!$title||empty($assignments)) cgd_ok('Nhap tieu de va phan cong');
        foreach($assignments as &$a){$a['completed']=0;$a['status']='assigned';}unset($a);
        $maxId=0;foreach($missions as $m){if(intval($m['id']??0)>$maxId)$maxId=intval($m['id']);}
        $missions[]=['id'=>$maxId+1,'title'=>$title,'assignments'=>$assignments,'status'=>'active','created_by'=>$uid,'created_at'=>date('c')];
    }

    if($action==='update_progress'){
        $missionId=intval($input['mission_id']??0);
        $completed=intval($input['completed']??0);
        foreach($missions as &$m){
            if(intval($m['id']??0)===$missionId){
                foreach($m['assignments'] as &$a){
                    if(intval($a['user_id']??0)===$uid){$a['completed']=$completed;if($completed>=intval($a['order_count']??0))$a['status']='done';}
                }unset($a);
                $allDone=true;foreach($m['assignments'] as $a){if(($a['status']??'')!=='done') $allDone=false;}
                if($allDone) $m['status']='completed';
            }
        }unset($m);
    }

    if($action==='complete'){
        $missionId=intval($input['mission_id']??0);
        foreach($missions as &$m){if(intval($m['id']??0)===$missionId){$m['status']='completed';$m['completed_at']=date('c');}}unset($m);
    }

    if(count($missions)>30) $missions=array_slice($missions,-30);
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($missions)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($missions))]);
    cgd_ok('OK!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
