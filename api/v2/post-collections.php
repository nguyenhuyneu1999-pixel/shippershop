<?php
// ShipperShop API v2 — Post Collections
// Curated collections of posts (like Pinterest boards)
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

function pc_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pc_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // List user's collections
    if(!$action||$action==='my'){
        if(!$uid) pc_fail('Login required',401);
        $collections=$d->fetchAll("SELECT bc.*,COUNT(bi.id) as item_count FROM bookmark_collections bc LEFT JOIN bookmark_items bi ON bc.id=bi.collection_id WHERE bc.user_id=? GROUP BY bc.id ORDER BY bc.created_at DESC",[$uid]);
        pc_ok('OK',$collections);
    }

    // Get collection posts
    if($action==='posts'){
        $colId=intval($_GET['collection_id']??0);
        if(!$colId) pc_fail('Missing collection_id');
        $col=$d->fetchOne("SELECT * FROM bookmark_collections WHERE id=?",[$colId]);
        if(!$col) pc_fail('Collection not found',404);
        // Check access (public or owner)
        $isPublic=intval($col['is_public']??0);
        if(!$isPublic&&(!$uid||intval($col['user_id'])!==$uid)) pc_fail('Private collection',403);

        $posts=$d->fetchAll("SELECT p.*,u.fullname,u.avatar,u.shipping_company FROM bookmark_items bi JOIN posts p ON bi.post_id=p.id JOIN users u ON p.user_id=u.id WHERE bi.collection_id=? AND p.`status`='active' ORDER BY bi.created_at DESC LIMIT 50",[$colId]);
        pc_ok('OK',['collection'=>$col,'posts'=>$posts]);
    }

    // Browse public collections
    if($action==='discover'){
        $collections=$d->fetchAll("SELECT bc.*,u.fullname as owner_name,u.avatar as owner_avatar,COUNT(bi.id) as item_count FROM bookmark_collections bc JOIN users u ON bc.user_id=u.id LEFT JOIN bookmark_items bi ON bc.id=bi.collection_id WHERE bc.is_public=1 GROUP BY bc.id ORDER BY item_count DESC LIMIT 20");
        pc_ok('OK',$collections);
    }

    pc_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create collection
    if($action==='create'){
        $name=trim($input['name']??'');
        $desc=trim($input['description']??'');
        $isPublic=!empty($input['is_public'])?1:0;
        if(!$name) pc_fail('Nhập tên bộ sưu tập');
        $pdo->prepare("INSERT INTO bookmark_collections (user_id,name,description,is_public,created_at) VALUES (?,?,?,?,NOW())")->execute([$uid,$name,$desc,$isPublic]);
        $id=intval($pdo->lastInsertId());
        if(!$id){$r=$pdo->query("SELECT MAX(id) as m FROM bookmark_collections");$id=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        pc_ok('Đã tạo!',['id'=>$id]);
    }

    // Add post to collection
    if($action==='add'){
        $colId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        if(!$colId||!$postId) pc_fail('Missing data');
        $col=$d->fetchOne("SELECT user_id FROM bookmark_collections WHERE id=?",[$colId]);
        if(!$col||intval($col['user_id'])!==$uid) pc_fail('Not your collection',403);
        $exists=$d->fetchOne("SELECT id FROM bookmark_items WHERE collection_id=? AND post_id=?",[$colId,$postId]);
        if($exists) pc_fail('Đã có trong bộ sưu tập');
        $pdo->prepare("INSERT INTO bookmark_items (collection_id,post_id,created_at) VALUES (?,?,NOW())")->execute([$colId,$postId]);
        pc_ok('Đã thêm vào bộ sưu tập!');
    }

    // Remove post from collection
    if($action==='remove'){
        $colId=intval($input['collection_id']??0);
        $postId=intval($input['post_id']??0);
        $col=$d->fetchOne("SELECT user_id FROM bookmark_collections WHERE id=?",[$colId]);
        if(!$col||intval($col['user_id'])!==$uid) pc_fail('Not your collection',403);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=? AND post_id=?",[$colId,$postId]);
        pc_ok('Đã xóa');
    }

    // Delete collection
    if($action==='delete'){
        $colId=intval($input['collection_id']??0);
        $col=$d->fetchOne("SELECT user_id FROM bookmark_collections WHERE id=?",[$colId]);
        if(!$col||intval($col['user_id'])!==$uid) pc_fail('Not your collection',403);
        $d->query("DELETE FROM bookmark_items WHERE collection_id=?",[$colId]);
        $d->query("DELETE FROM bookmark_collections WHERE id=?",[$colId]);
        pc_ok('Đã xóa bộ sưu tập');
    }

    pc_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
