<?php
// ShipperShop API v2 — User Goals
// Set personal goals: deliveries/month, posts/week, followers target
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();

function ug_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$key='user_goals_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $goals=$row?json_decode($row['value'],true):[];

    // Calculate progress for each goal
    foreach($goals as &$g){
        $current=0;$type=$g['type']??'';
        if($type==='posts_month') $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')",[$uid])['c']);
        elseif($type==='posts_week') $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id=? AND `status`='active' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",[$uid])['c']);
        elseif($type==='followers') $current=intval($d->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id=?",[$uid])['c']);
        elseif($type==='xp') $current=intval($d->fetchOne("SELECT COALESCE(SUM(xp),0) as s FROM user_xp WHERE user_id=?",[$uid])['s']);
        elseif($type==='streak') $current=intval($d->fetchOne("SELECT current_streak FROM user_streaks WHERE user_id=?",[$uid])['current_streak']??0);
        $g['current']=$current;
        $g['progress']=($g['target']??1)>0?min(100,round($current/intval($g['target'])*100)):0;
        $g['completed']=$current>=intval($g['target']??0);
    }unset($g);
    ug_ok('OK',['goals'=>$goals]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'add';
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $goals=$row?json_decode($row['value'],true):[];

    if($action==='add'){
        $type=$input['type']??'posts_month';
        $target=intval($input['target']??10);
        $name=$input['name']??'';
        $icons=['posts_month'=>'📝','posts_week'=>'✍️','followers'=>'👥','xp'=>'⭐','streak'=>'🔥','custom'=>'🎯'];
        $names=['posts_month'=>'Bai/thang','posts_week'=>'Bai/tuan','followers'=>'Nguoi theo doi','xp'=>'XP','streak'=>'Streak'];
        $maxId=0;foreach($goals as $g){if(intval($g['id']??0)>$maxId)$maxId=intval($g['id']);}
        $goals[]=['id'=>$maxId+1,'type'=>$type,'target'=>$target,'name'=>$name?:($names[$type]??$type),'icon'=>$icons[$type]??'🎯','created_at'=>date('c')];
        if(count($goals)>10) ug_ok('Toi da 10 muc tieu');
    }

    if($action==='delete'){
        $goalId=intval($input['goal_id']??0);
        $goals=array_values(array_filter($goals,function($g) use($goalId){return intval($g['id']??0)!==$goalId;}));
    }

    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode(array_values($goals)),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode(array_values($goals))]);
    ug_ok($action==='delete'?'Da xoa':'Da them muc tieu!');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
