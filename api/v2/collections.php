<?php
// ShipperShop API v2 — Post Collections
// Organize saved posts into named collections (like Pinterest boards)
// session removed: JWT auth only
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

function cl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function cl_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List my collections
    if(!$action||$action==='list'){
        $colls=$d->fetchAll("SELECT bc.*,(SELECT COUNT(*) FROM bookmark_items bi WHERE bi.collection_id=bc.id) as item_count FROM bookmark_collections bc WHERE bc.user_id=? ORDER BY bc.created_at DESC",[$uid]);
        cl_ok('OK',$colls);
    }
    // Get collection items
    if($action==='items'){
        $collId=intval($_GET['collection_id']??0);
        if(!$collId) cl_fail('Missing collection_id');
        $coll=$d->fetchOne("SELECT * FROM bookmark_collections WHERE id=? AND user_id=?",[$collId,$uid]);
        if(!$coll) cl_fail('Not found',404);
        $items=$d->fetchAll("SELECT bi.*,p.content,p.likes_count,p.comments_count,p.created_at as post_date,u.fullname,u.avatar FROM bookmark_items bi JOIN posts p ON bi.post_id=p.id JOIN users u ON p.user_id=u.id WHERE bi.collection_id=? ORDER BY bi.created_at DESC",[$collId]);
        cl_ok('OK',['collection'=>$coll,'items'=>$items]);
    }
    cl_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Create collection
    if($action==='create'){
        $name=trim($input['name']??'');
        $desc=trim($input['description']??'');
        $isPublic=!empty($input['is_public']);
        if(!$name) cl_fail('Nhập tên bộ sưu tập');
        $count=intval($d->fetchOne("SELECT COUNT(*) as c FROM bookmark_collections WHERE user_id=?",[$uid])['c']);
        if($count>=20) cl_fail('Tối đa 20 bộ sưu tập');
        $pdo->prepare("INSERT INTO bookmark_collections (user_id,name,description,is_public,created_at) VALUES (?,?,?,?,NOW())")->execute([$uid,$name,$desc,$isPublic?1:0]);
        $id=intval($pdo->lastInsertId());
        cl_ok('Đã tạo!',['id'=>$id]);
    }

    // Add post to collection
    if($action==='add'){
        $collId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        if(!$collId||!$postId) cl_fail('Missing data');
        $coll=$d->fetchOne("SELECT id FROM bookmark_collections WHERE id=? AND user_id=?",[$collId,$uid]);
        if(!$coll) cl_fail('Collection not found',404);
        $exists=$d->fetchOne("SELECT id FROM bookmark_items WHERE collection_id=? AND post_id=?",[$collId,$postId]);
        if($exists) cl_fail('Đã có trong bộ sưu tập');
        $pdo->prepare("INSERT INTO bookmark_items (collection_id,post_id,created_at) VALUES (?,?,NOW())")->execute([$collId,$postId]);
        cl_ok('Đã thêm!');
    }

    // Remove from collection
    if($action==='remove'){
        $collId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND post_id=? AND collection_id IN (SELECT id FROM bookmark_collections WHERE user_id=?)",[$collId,$postId,$uid]);
        cl_ok('Đã xóa');
    }

    // Delete collection
    if($action==='delete'){
        $collId=intval($input['collection_id']??0);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND collection_id IN (SELECT id FROM bookmark_collections WHERE user_id=?)",[$collId,$uid]);
        $d->query("DELETE FROM bookmark_collections WHERE id=? AND user_id=?",[$collId,$uid]);
        cl_ok('Đã xóa bộ sưu tập');
    }

    cl_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
