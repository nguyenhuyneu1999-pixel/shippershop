<?php
// ShipperShop API v2 — Post Templates
// Pre-made content templates for common shipper posts
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$action=$_GET['action']??'';

// Built-in templates
$TEMPLATES=[
    ['id'=>1,'category'=>'delivery','title'=>'Nhận đơn','icon'=>'📦','template'=>"📦 NHẬN ĐƠN — {area}\n\nHãng: {company}\nKhu vực: {area}\nPhí ship: {price}đ\nLiên hệ: {phone}\n\n#nhậnđơn #{company_tag}"],
    ['id'=>2,'category'=>'delivery','title'=>'Giao hàng thành công','icon'=>'✅','template'=>"✅ GIAO THÀNH CÔNG!\n\nĐơn: #{order_id}\nKhu vực: {area}\nThời gian: {time}\n\nCảm ơn quý khách! 🙏\n\n#giaohàng #thànhcông"],
    ['id'=>3,'category'=>'search','title'=>'Tìm đơn','icon'=>'🔍','template'=>"🔍 TÌM ĐƠN — {area}\n\nHãng: {company}\nKhu vực nhận: {pickup}\nKhu vực giao: {delivery}\nLoại hàng: {type}\n\nAi có đơn liên hệ mình nhé! 📱\n\n#tìmđơn #{area_tag}"],
    ['id'=>4,'category'=>'tip','title'=>'Chia sẻ mẹo','icon'=>'💡','template'=>"💡 MẸO HAY cho anh em shipper:\n\n{content}\n\nĐừng quên share cho anh em nhé! 🤝\n\n#mẹohay #shipper"],
    ['id'=>5,'category'=>'review','title'=>'Đánh giá hãng','icon'=>'⭐','template'=>"⭐ ĐÁNH GIÁ — {company}\n\nĐiểm: {rating}/5\nƯu điểm: {pros}\nNhược điểm: {cons}\n\nTổng kết: {summary}\n\n#đánhgiá #{company_tag}"],
    ['id'=>6,'category'=>'alert','title'=>'Cảnh báo lừa đảo','icon'=>'🚨','template'=>"🚨 CẢNH BÁO LỪA ĐẢO!\n\nKhu vực: {area}\nHình thức: {type}\nMô tả: {description}\n\nAnh em cẩn thận! Báo công an nếu gặp.\n\n#cảnhbáo #lừađảo"],
    ['id'=>7,'category'=>'job','title'=>'Tuyển shipper','icon'=>'💼','template'=>"💼 TUYỂN SHIPPER\n\nHãng: {company}\nKhu vực: {area}\nThu nhập: {salary}\nYêu cầu: {requirements}\n\nLiên hệ: {contact}\n\n#tuyểndụng #shipper #{area_tag}"],
    ['id'=>8,'category'=>'traffic','title'=>'Cảnh báo giao thông','icon'=>'🚦','template'=>"🚦 CẢNH BÁO GIAO THÔNG\n\n📍 {location}\n⏰ {time}\n📝 {description}\n\nAnh em tránh khu vực này!\n\n#giaothông #cảnhbáo"],
];

if($action==='list'||!$action){
    $category=$_GET['category']??'';
    $filtered=$TEMPLATES;
    if($category){
        $filtered=array_values(array_filter($TEMPLATES,function($t) use($category){return $t['category']===$category;}));
    }
    echo json_encode(['success'=>true,'data'=>$filtered],JSON_UNESCAPED_UNICODE);exit;
}

if($action==='categories'){
    echo json_encode(['success'=>true,'data'=>[
        ['id'=>'delivery','name'=>'Giao hàng','icon'=>'📦'],
        ['id'=>'search','name'=>'Tìm đơn','icon'=>'🔍'],
        ['id'=>'tip','name'=>'Mẹo hay','icon'=>'💡'],
        ['id'=>'review','name'=>'Đánh giá','icon'=>'⭐'],
        ['id'=>'alert','name'=>'Cảnh báo','icon'=>'🚨'],
        ['id'=>'job','name'=>'Tuyển dụng','icon'=>'💼'],
        ['id'=>'traffic','name'=>'Giao thông','icon'=>'🚦'],
    ]],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);
