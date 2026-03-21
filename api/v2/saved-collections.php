<?php
// ShipperShop API v2 — Saved Collections
// Organize saved posts into named collections
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

function sc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List collections with post count
    if(!$action||$action==='list'){
        $collections=$d->fetchAll("SELECT bc.*,(SELECT COUNT(*) FROM bookmark_items bi WHERE bi.collection_id=bc.id) as post_count FROM bookmark_collections bc WHERE bc.user_id=? ORDER BY bc.created_at DESC",[$uid]);
        // Also get unsorted saved posts
        $unsorted=intval($d->fetchOne("SELECT COUNT(*) as c FROM saved_posts sp WHERE sp.user_id=? AND sp.id NOT IN (SELECT post_id FROM bookmark_items WHERE collection_id IN (SELECT id FROM bookmark_collections WHERE user_id=?))",[$uid,$uid])['c']);
        sc_ok('OK',['collections'=>$collections,'unsorted_count'=>$unsorted]);
    }

    // Get posts in a collection
    if($action==='posts'){
        $collId=intval($_GET['collection_id']??0);
        $page=max(1,intval($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;

        if($collId){
            $posts=$d->fetchAll("SELECT p.*,u.fullname,u.avatar FROM bookmark_items bi JOIN posts p ON bi.post_id=p.id JOIN users u ON p.user_id=u.id WHERE bi.collection_id=? AND p.`status`='active' ORDER BY bi.created_at DESC LIMIT $limit OFFSET $offset",[$collId]);
        }else{
            // Unsorted
            $posts=$d->fetchAll("SELECT p.*,u.fullname,u.avatar FROM saved_posts sp JOIN posts p ON sp.post_id=p.id JOIN users u ON p.user_id=u.id WHERE sp.user_id=? AND p.`status`='active' ORDER BY sp.created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
        }
        sc_ok('OK',['posts'=>$posts,'page'=>$page]);
    }

    sc_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Create collection
    if($action==='create'){
        $name=trim($input['name']??'');
        $icon=$input['icon']??'📁';
        if(!$name) sc_fail('Nhập tên bộ sưu tập');
        $count=intval($d->fetchOne("SELECT COUNT(*) as c FROM bookmark_collections WHERE user_id=?",[$uid])['c']);
        if($count>=20) sc_fail('Tối đa 20 bộ sưu tập');
        $pdo->prepare("INSERT INTO bookmark_collections (user_id,name,description,created_at) VALUES (?,?,?,NOW())")->execute([$uid,$name,$icon]);
        $id=intval($pdo->lastInsertId());
        if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM bookmark_collections");$id=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        sc_ok('Đã tạo!',['id'=>$id]);
    }

    // Add post to collection
    if($action==='add'){
        $collId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        if(!$collId||!$postId) sc_fail('Missing data');
        // Verify ownership
        $coll=$d->fetchOne("SELECT id FROM bookmark_collections WHERE id=? AND user_id=?",[$collId,$uid]);
        if(!$coll) sc_fail('Collection not found',404);
        try{$pdo->prepare("INSERT IGNORE INTO bookmark_items (collection_id,post_id,created_at) VALUES (?,?,NOW())")->execute([$collId,$postId]);}catch(\Throwable $e){}
        sc_ok('Đã thêm vào bộ sưu tập');
    }

    // Remove from collection
    if($action==='remove'){
        $collId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND post_id=?",[$collId,$postId]);
        sc_ok('Đã xóa');
    }

    // Delete collection
    if($action==='delete'){
        $collId=intval($input['collection_id']??0);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND collection_id IN (SELECT id FROM bookmark_collections WHERE user_id=?)",[$collId,$uid]);
        $d->query("DELETE FROM bookmark_collections WHERE id=? AND user_id=?",[$collId,$uid]);
        sc_ok('Đã xóa bộ sưu tập');
    }

    // Rename collection
    if($action==='rename'){
        $collId=intval($input['collection_id']??0);
        $name=trim($input['name']??'');
        if(!$collId||!$name) sc_fail('Missing data');
        $d->query("UPDATE bookmark_collections SET name=? WHERE id=? AND user_id=?",[$name,$collId,$uid]);
        sc_ok('Đã đổi tên');
    }

    sc_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
