<?php
// ShipperShop API v2 — Bookmarks & Collections
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function bk_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function bk_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

// Public: view shared collection (no auth)
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='shared'){
    $key=trim($_GET['key']??'');
    if(!$key) bk_ok('OK',[]);
    $col=$d->fetchOne("SELECT bc.*,u.fullname as owner_name,u.avatar as owner_avatar FROM bookmark_collections bc JOIN users u ON bc.user_id=u.id WHERE bc.share_key=?",[$key]);
    if(!$col) bk_ok('OK',null);
    $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar FROM bookmark_items bi JOIN posts p ON bi.post_id=p.id LEFT JOIN users u ON p.user_id=u.id WHERE bi.collection_id=? AND p.`status`='active' ORDER BY bi.created_at DESC LIMIT 50",[$col['id']]);
    bk_ok('OK',['collection'=>$col,'posts'=>$posts]);
}

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=require_auth();

    // List collections
    if(!$action||$action==='collections'){
        $cols=$d->fetchAll("SELECT * FROM bookmark_collections WHERE user_id=? ORDER BY created_at DESC",[$uid]);
        // Also get total saved posts count
        $totalSaved=intval($d->fetchOne("SELECT COUNT(*) as c FROM saved_posts WHERE user_id=?",[$uid])['c']);
        bk_ok('OK',['collections'=>$cols,'total_saved'=>$totalSaved]);
    }

    // Saved posts (all or by collection)
    if($action==='posts'){
        $colId=intval($_GET['collection_id']??0);
        $page=max(1,intval($_GET['page']??1));$limit=15;$offset=($page-1)*$limit;

        if($colId){
            $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM bookmark_items WHERE collection_id=? AND user_id=?",[$colId,$uid])['c']);
            $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company FROM bookmark_items bi JOIN posts p ON bi.post_id=p.id LEFT JOIN users u ON p.user_id=u.id WHERE bi.collection_id=? AND bi.user_id=? AND p.`status`='active' ORDER BY bi.created_at DESC LIMIT $limit OFFSET $offset",[$colId,$uid]);
        }else{
            $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM saved_posts WHERE user_id=?",[$uid])['c']);
            $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company FROM saved_posts sp JOIN posts p ON sp.post_id=p.id LEFT JOIN users u ON p.user_id=u.id WHERE sp.user_id=? AND p.`status`='active' ORDER BY sp.created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
        }

        foreach($posts as &$p){$p['user_saved']=true;}unset($p);
        echo json_encode(['success'=>true,'data'=>['posts'=>$posts,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total]]]);exit;
    }

    bk_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create collection
    if($action==='create_collection'){
        $name=trim($input['name']??'');
        if(!$name) bk_fail('Tên collection trống');
        $icon=$input['icon']??null;
        $pdo->prepare("INSERT INTO bookmark_collections (user_id,name,icon,created_at) VALUES (?,?,?,NOW())")->execute([$uid,$name,$icon]);
        $id=intval($pdo->lastInsertId());
        bk_ok('Đã tạo!',['id'=>$id]);
    }

    // Share collection (generate public link)
    if($action==='share_collection'){
        $colId=intval($input['collection_id']??0);
        if(!$colId) bk_fail('Missing collection_id');
        $col=$d->fetchOne("SELECT id,name FROM bookmark_collections WHERE id=? AND user_id=?",[$colId,$uid]);
        if(!$col) bk_fail('Not found',404);
        $shareKey=substr(md5($colId.'_'.$uid.'_'.time()),0,12);
        $d->query("UPDATE bookmark_collections SET share_key=? WHERE id=?",[$shareKey,$colId]);
        bk_ok('Link chia sẻ',['share_url'=>'https://shippershop.vn/bookmarks.html?share='.$shareKey,'share_key'=>$shareKey]);
    }

    // Delete collection
    if($action==='delete_collection'){
        $colId=intval($input['collection_id']??0);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND user_id=?",[$colId,$uid]);
        $d->query("DELETE FROM bookmark_collections WHERE id=? AND user_id=?",[$colId,$uid]);
        bk_ok('Đã xóa');
    }

    // Add post to collection
    if($action==='add_to_collection'){
        $colId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        if(!$colId||!$postId) bk_fail('Missing data');
        // Verify collection belongs to user
        $col=$d->fetchOne("SELECT id FROM bookmark_collections WHERE id=? AND user_id=?",[$colId,$uid]);
        if(!$col) bk_fail('Collection không tồn tại');
        try{
            $pdo->prepare("INSERT IGNORE INTO bookmark_items (collection_id,post_id,user_id,created_at) VALUES (?,?,?,NOW())")->execute([$colId,$postId,$uid]);
            $d->query("UPDATE bookmark_collections SET post_count=post_count+1 WHERE id=?",[$colId]);
        }catch(\Throwable $e){}
        bk_ok('Đã thêm vào collection!');
    }

    // Remove from collection
    if($action==='remove_from_collection'){
        $colId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND post_id=? AND user_id=?",[$colId,$postId,$uid]);
        $d->query("UPDATE bookmark_collections SET post_count=GREATEST(post_count-1,0) WHERE id=?",[$colId]);
        bk_ok('Đã xóa khỏi collection');
    }

    bk_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
