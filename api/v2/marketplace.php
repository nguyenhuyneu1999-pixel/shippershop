<?php
/**
 * ShipperShop API v2 — Marketplace
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';
require_once __DIR__.'/../../includes/upload-handler.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();
$pdo=$d->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List
    if(!$action||$action==='list'){
        $page=max(1,intval($_GET['page']??1));
        $limit=min(intval($_GET['limit']??20),50);
        $offset=($page-1)*$limit;
        $search=$_GET['search']??'';
        $category=$_GET['category']??'';
        $condition=$_GET['condition']??'';
        $sort=$_GET['sort']??'newest';
        $minPrice=intval($_GET['min_price']??0);
        $maxPrice=intval($_GET['max_price']??0);

        $w=["m.`status`='active'"];$p=[];
        if($search){$w[]="(m.title LIKE ? OR m.description LIKE ?)";$p[]='%'.$search.'%';$p[]='%'.$search.'%';}
        if($category){$w[]="m.category=?";$p[]=$category;}
        if($condition){$w[]="m.`condition`=?";$p[]=$condition;}
        if($minPrice>0){$w[]="m.price>=?";$p[]=$minPrice;}
        if($maxPrice>0){$w[]="m.price<=?";$p[]=$maxPrice;}
        $wc=implode(' AND ',$w);

        $ob=$sort==='price_asc'?'m.price ASC':($sort==='price_desc'?'m.price DESC':'m.created_at DESC');

        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM marketplace_listings m WHERE $wc",$p)['c']);
        $rows=$d->fetchAll("SELECT m.*,u.fullname as seller_name,u.avatar as seller_avatar FROM marketplace_listings m LEFT JOIN users u ON m.user_id=u.id WHERE $wc ORDER BY $ob LIMIT $limit OFFSET $offset",$p);
        echo json_encode(['success'=>true,'data'=>['listings'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // Single listing
    if($action==='detail'){
        $id=intval($_GET['id']??0);
        $item=$d->fetchOne("SELECT m.*,u.fullname as seller_name,u.avatar as seller_avatar,u.shipping_company FROM marketplace_listings m LEFT JOIN users u ON m.user_id=u.id WHERE m.id=?",[$id]);
        if(!$item) fail('Không tìm thấy',404);
        $reviews=$d->fetchAll("SELECT r.*,u.fullname as reviewer_name,u.avatar as reviewer_avatar FROM reviews r LEFT JOIN users u ON r.user_id=u.id WHERE r.listing_id=? ORDER BY r.created_at DESC LIMIT 10",[$id]);
        $item['reviews']=$reviews;
        ok('OK',$item);
    }

    ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create listing
    if(!$action||$action==='create'){
        rate_enforce('listing_create',5,3600);
        $title=trim($input['title']??'');
        $desc=trim($input['description']??'');
        $price=floatval($input['price']??0);
        $category=trim($input['category']??'other');
        $condition=trim($input['condition']??'used');
        if(!$title||strlen($title)<5) fail('Tiêu đề tối thiểu 5 ký tự');
        if($price<0) fail('Giá không hợp lệ');

        $ins=$pdo->prepare("INSERT INTO marketplace_listings (user_id,title,description,price,category,`condition`,`status`,created_at) VALUES (?,?,?,?,?,?,'active',NOW())");
        $ins->execute([$uid,$title,$desc,$price,$category,$condition]);
        $lid=intval($pdo->lastInsertId());
        if(!$lid){$r=$pdo->query("SELECT MAX(id) as m FROM marketplace_listings");$lid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        ok('Đã đăng!',['id'=>$lid]);
    }

    // Edit listing
    if($action==='edit'){
        $id=intval($input['id']??0);
        $item=$d->fetchOne("SELECT user_id FROM marketplace_listings WHERE id=?",[$id]);
        if(!$item||intval($item['user_id'])!==$uid) fail('Không có quyền',403);
        $fields=[];$params=[];
        if(isset($input['title'])){$fields[]="title=?";$params[]=trim($input['title']);}
        if(isset($input['description'])){$fields[]="description=?";$params[]=trim($input['description']);}
        if(isset($input['price'])){$fields[]="price=?";$params[]=floatval($input['price']);}
        if(isset($input['category'])){$fields[]="category=?";$params[]=trim($input['category']);}
        if(isset($input['condition'])){$fields[]="`condition`=?";$params[]=trim($input['condition']);}
        if(!empty($fields)){$params[]=$id;$d->query("UPDATE marketplace_listings SET ".implode(',',$fields)." WHERE id=?",$params);}
        ok('Đã cập nhật');
    }

    // Delete listing
    if($action==='delete'){
        $id=intval($input['id']??0);
        $item=$d->fetchOne("SELECT user_id FROM marketplace_listings WHERE id=?",[$id]);
        if(!$item) fail('Not found',404);
        $user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(intval($item['user_id'])!==$uid&&($user['role']??'')!=='admin') fail('Không có quyền',403);
        $d->query("UPDATE marketplace_listings SET `status`='deleted' WHERE id=?",[$id]);
        ok('Đã xóa');
    }

    fail('Action không hợp lệ');
}

fail('Method không hỗ trợ',405);
