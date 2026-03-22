<?php
/**
 * ShipperShop API v2 — Notifications
 * Schema: notifications(id, user_id, type, title, message, data, is_read, created_at)
 */
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
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($method==='GET'){
    $uid=require_auth();

    if($action==='count'){
        $c=$d->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE user_id=? AND is_read=0",[$uid]);
        echo json_encode(['success'=>true,'count'=>intval($c['c']??0)]);exit;
    }

    // List
    if($action==='list'||!$action){
        $page=max(1,intval($_GET['page']??1));
        $limit=min(intval($_GET['limit']??20),50);
        $offset=($page-1)*$limit;
        $type=$_GET['type']??'';

        $where="user_id=?";$params=[$uid];
        if($type){$where.=" AND type=?";$params[]=$type;}

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM notifications WHERE $where",$params)['c']);
        $rows=$d->fetchAll("SELECT id,type,title,message,data,is_read,created_at FROM notifications WHERE $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset",$params);
        foreach($rows as &$r){
            $r['is_read']=intval($r['is_read']);
            if($r['data']) $r['data']=json_decode($r['data'],true);
        }
        unset($r);
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
            $d->query("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?",[$nid,$uid]);
        }
        ok('OK');
    }

    if($action==='mark_all_read'){
        $d->query("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0",[$uid]);
        ok('Đã đọc tất cả');
    }

    fail('Action không hợp lệ');
}
fail('Method không hỗ trợ',405);
