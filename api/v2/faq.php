<?php
// ShipperShop API v2 — FAQ
// Frequently asked questions, searchable, categorized
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=3600');

$FAQS=[
    ['id'=>1,'cat'=>'account','q'=>'Lam sao de dang ky tai khoan?','a'=>'Vao trang register.html, nhap email va mat khau. Sau do xac nhan email de kich hoat tai khoan.'],
    ['id'=>2,'cat'=>'account','q'=>'Lam sao de doi mat khau?','a'=>'Vao Profile > Cai dat > Doi mat khau. Nhap mat khau cu va mat khau moi.'],
    ['id'=>3,'cat'=>'account','q'=>'Lam sao de xac minh tai khoan?','a'=>'Can co tren 5 bai viet va du dieu kien. Vao Profile > Yeu cau xac minh.'],
    ['id'=>4,'cat'=>'post','q'=>'Lam sao de dang bai?','a'=>'Nhan nut + o goc duoi phai hoac o tao bai viet tren trang chu. Nhap noi dung, them anh va nhan Dang.'],
    ['id'=>5,'cat'=>'post','q'=>'Co the chinh sua bai sau khi dang khong?','a'=>'Co! Nhan vao menu 3 cham tren bai viet cua ban, chon Chinh sua.'],
    ['id'=>6,'cat'=>'post','q'=>'Lam sao de xoa bai viet?','a'=>'Nhan menu 3 cham > Xoa bai viet. Bai se bi an khoi feed.'],
    ['id'=>7,'cat'=>'wallet','q'=>'Lam sao de nap tien vao vi?','a'=>'Vao Wallet > Nap tien > Chon so tien > Chuyen khoan theo huong dan. Admin se duyet trong 24h.'],
    ['id'=>8,'cat'=>'wallet','q'=>'Lam sao de dang ky goi PRO/VIP?','a'=>'Vao Wallet > Chon goi > Nhap PIN xac nhan. So du se bi tru tu vi.'],
    ['id'=>9,'cat'=>'delivery','q'=>'ShipperShop la gi?','a'=>'ShipperShop la nen tang cong dong shipper Viet Nam. Chia se, ket noi, mua ban giua cac shipper 63 tinh thanh.'],
    ['id'=>10,'cat'=>'delivery','q'=>'Lam sao de tim don giao?','a'=>'Su dung bo loc tren trang chu: chon tinh/thanh, quan/huyen de tim bai viet gan ban.'],
    ['id'=>11,'cat'=>'group','q'=>'Lam sao de tao nhom?','a'=>'Vao Groups > Tao nhom. Nhap ten, mo ta, chon danh muc va moi thanh vien.'],
    ['id'=>12,'cat'=>'group','q'=>'Lam sao de tham gia nhom?','a'=>'Tim nhom trong Discover > Nhan Tham gia. Mot so nhom can admin duyet.'],
];

$action=$_GET['action']??'';
$cat=$_GET['category']??'';
$q=trim($_GET['q']??'');

$filtered=$FAQS;
if($cat) $filtered=array_values(array_filter($filtered,function($f) use($cat){return $f['cat']===$cat;}));
if($q) $filtered=array_values(array_filter($filtered,function($f) use($q){return mb_stripos($f['q'],$q)!==false||mb_stripos($f['a'],$q)!==false;}));

$categories=[
    ['id'=>'account','name'=>'Tai khoan','icon'=>'👤'],
    ['id'=>'post','name'=>'Bai viet','icon'=>'📝'],
    ['id'=>'wallet','name'=>'Vi tien','icon'=>'💰'],
    ['id'=>'delivery','name'=>'Giao hang','icon'=>'📦'],
    ['id'=>'group','name'=>'Nhom','icon'=>'👥'],
];

if($action==='categories'){
    echo json_encode(['success'=>true,'data'=>$categories],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>['faqs'=>$filtered,'categories'=>$categories,'total'=>count($filtered)]],JSON_UNESCAPED_UNICODE);
