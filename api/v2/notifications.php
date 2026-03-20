<?php
/**
 * ShipperShop API v2 — Notifications
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
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
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($method==='GET'){
    $uid=require_auth();

    // Count unread
    if($action==='count'){
        $c=$d->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND id NOT IN (SELECT notification_id FROM notification_reads WHERE user_id=?)",[$uid,$uid]);
        echo json_encode(['success'=>true,'count'=>intval($c['c']??0)]);exit;
    }

    // List
    if($action==='list'||!$action){
        $page=max(1,intval($_GET['page']??1));
        $limit=min(intval($_GET['limit']??20),50);
        $offset=($page-1)*$limit;
        $type=$_GET['type']??'';

        $where="n.user_id=?";$params=[$uid];
        if($type){$where.=" AND n.type=?";$params[]=$type;}

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM notifications n WHERE $where",$params)['c']);
        $rows=$d->fetchAll("SELECT n.*,(SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=?) as is_read FROM notifications n WHERE $where ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset",array_merge([$uid],$params));
        foreach($rows as &$r){$r['is_read']=!!$r['is_read'];}unset($r);
        echo json_encode(['success'=>true,'data'=>['notifications'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    ok('OK',[]);
}

if($method==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    if($action==='mark_read'){
        $nid=intval($input['notification_id']??0);
        if($nid){
            $pdo=$d->getConnection();
            try{$pdo->prepare("INSERT IGNORE INTO notification_reads (notification_id,user_id,read_at) VALUES (?,?,NOW())")->execute([$nid,$uid]);}catch(\Throwable $e){}
        }
        ok('OK');
    }

    if($action==='mark_all_read'){
        $unread=$d->fetchAll("SELECT id FROM notifications WHERE user_id=? AND id NOT IN (SELECT notification_id FROM notification_reads WHERE user_id=?)",[$uid,$uid]);
        $pdo=$d->getConnection();
        foreach($unread as $n){
            try{$pdo->prepare("INSERT IGNORE INTO notification_reads (notification_id,user_id,read_at) VALUES (?,?,NOW())")->execute([$n['id'],$uid]);}catch(\Throwable $e){}
        }
        ok('Đã đọc tất cả',['marked'=>count($unread)]);
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
