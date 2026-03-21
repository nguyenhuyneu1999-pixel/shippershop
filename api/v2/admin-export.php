<?php
// ShipperShop API v2 — Admin Data Export (CSV)
// Export users, posts, transactions as CSV for admin
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

$d=db();$action=$_GET['action']??'users';
$format=$_GET['format']??'json';

function ae_fail($msg,$code=400){header('Content-Type: application/json');http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
if(!$user||$user['role']!=='admin') ae_fail('Admin only',403);

rate_enforce('admin_export',5,3600);

$data=[];$headers=[];

if($action==='users'){
    $headers=['ID','Tên','Email','SĐT','Hãng','Posts','Followers','Following','Ngày tạo','Trạng thái'];
    $rows=$d->fetchAll("SELECT id,fullname,email,phone,shipping_company,total_posts,total_followers,total_following,created_at,`status` FROM users ORDER BY id");
    foreach($rows as $r) $data[]=[$r['id'],$r['fullname'],$r['email'],$r['phone'],$r['shipping_company'],$r['total_posts'],$r['total_followers'],$r['total_following'],$r['created_at'],$r['status']];
}

if($action==='posts'){
    $days=min(intval($_GET['days']??30),365);
    $headers=['ID','User','Nội dung','Likes','Comments','Shares','Type','Province','Ngày tạo'];
    $rows=$d->fetchAll("SELECT p.id,u.fullname,p.content,p.likes_count,p.comments_count,p.shares_count,p.type,p.province,p.created_at FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.`status`='active' AND p.created_at>=DATE_SUB(NOW(),INTERVAL $days DAY) ORDER BY p.created_at DESC");
    foreach($rows as $r) $data[]=[$r['id'],$r['fullname'],mb_substr($r['content']??'',0,100),$r['likes_count'],$r['comments_count'],$r['shares_count'],$r['type'],$r['province'],$r['created_at']];
}

if($action==='transactions'){
    $headers=['ID','User','Loại','Số tiền','Mô tả','Ngày'];
    $rows=$d->fetchAll("SELECT wt.id,u.fullname,wt.type,wt.amount,wt.description,wt.created_at FROM wallet_transactions wt LEFT JOIN users u ON wt.user_id=u.id ORDER BY wt.created_at DESC LIMIT 1000");
    foreach($rows as $r) $data[]=[$r['id'],$r['fullname'],$r['type'],$r['amount'],$r['description'],$r['created_at']];
}

if($action==='audit'){
    $headers=['ID','User','Action','Detail','IP','Ngày'];
    $rows=$d->fetchAll("SELECT a.id,u.fullname,a.action,a.detail,a.ip,a.created_at FROM audit_log a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.created_at DESC LIMIT 500");
    foreach($rows as $r) $data[]=[$r['id'],$r['fullname'],$r['action'],mb_substr($r['detail']??'',0,80),$r['ip'],$r['created_at']];
}

// CSV output
if($format==='csv'){
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=shippershop-'.$action.'-'.date('Y-m-d').'.csv');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    $out=fopen('php://output','w');
    fputcsv($out,$headers);
    foreach($data as $row) fputcsv($out,$row);
    fclose($out);
    exit;
}

// JSON output
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>true,'data'=>['headers'=>$headers,'rows'=>$data,'count'=>count($data),'exported_at'=>date('Y-m-d H:i:s')]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    ae_fail('Error: '.$e->getMessage());
}
