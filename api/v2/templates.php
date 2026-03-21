<?php
// ShipperShop API v2 — Post Templates
// Pre-made post formats: delivery update, route share, tip, question, review
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

function tp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

$TEMPLATES=[
    ['id'=>'delivery_update','name'=>'Cập nhật giao hàng','icon'=>'📦','category'=>'work',
     'template'=>"📦 Cập nhật giao hàng\n\n🛵 Hãng: {company}\n📍 Khu vực: {area}\n📊 Số đơn hôm nay: {orders}\n💰 Thu nhập: {income}\n\n💬 Nhận xét: {note}",
     'fields'=>['company','area','orders','income','note']],

    ['id'=>'route_share','name'=>'Chia sẻ tuyến đường','icon'=>'🗺️','category'=>'work',
     'template'=>"🗺️ Chia sẻ tuyến đường\n\n📍 Từ: {from}\n📍 Đến: {to}\n⏱️ Thời gian: {duration}\n🚧 Tình trạng: {condition}\n\n💡 Mẹo: {tip}",
     'fields'=>['from','to','duration','condition','tip']],

    ['id'=>'tip','name'=>'Mẹo shipper','icon'=>'💡','category'=>'knowledge',
     'template'=>"💡 Mẹo cho shipper\n\n📌 Chủ đề: {topic}\n\n{content}\n\n#meo #shipper #{tag}",
     'fields'=>['topic','content','tag']],

    ['id'=>'question','name'=>'Hỏi đáp','icon'=>'❓','category'=>'community',
     'template'=>"❓ Hỏi cộng đồng\n\n{question}\n\n📍 Khu vực: {area}\n🛵 Hãng: {company}\n\nAi biết giúp mình với! 🙏",
     'fields'=>['question','area','company']],

    ['id'=>'review','name'=>'Đánh giá','icon'=>'⭐','category'=>'knowledge',
     'template'=>"⭐ Đánh giá: {subject}\n\nĐiểm: {'+'*rating}{'☆'*(5-rating)}\n\n👍 Ưu điểm: {pros}\n👎 Nhược điểm: {cons}\n\n💬 Tổng kết: {summary}",
     'fields'=>['subject','rating','pros','cons','summary']],

    ['id'=>'income_report','name'=>'Báo cáo thu nhập','icon'=>'💰','category'=>'work',
     'template'=>"💰 Báo cáo thu nhập {period}\n\n🛵 Hãng: {company}\n📦 Tổng đơn: {total_orders}\n💵 Tổng thu nhập: {total_income}\n📊 TB/đơn: {avg_per_order}\n⏱️ Số giờ làm: {hours}\n💵 TB/giờ: {avg_per_hour}\n\n{note}",
     'fields'=>['period','company','total_orders','total_income','avg_per_order','hours','avg_per_hour','note']],

    ['id'=>'traffic_report','name'=>'Báo cáo giao thông','icon'=>'🚦','category'=>'community',
     'template'=>"🚦 Cảnh báo giao thông\n\n📍 Vị trí: {location}\n⚠️ Tình trạng: {status}\n⏱️ Dự kiến: {duration}\n\n💡 Gợi ý: {suggestion}",
     'fields'=>['location','status','duration','suggestion']],

    ['id'=>'achievement','name'=>'Thành tựu','icon'=>'🏆','category'=>'personal',
     'template'=>"🏆 {title}\n\n{description}\n\n#thanhcong #shipper",
     'fields'=>['title','description']],
];

try {

// List all templates
if(!$action||$action==='list'){
    $category=$_GET['category']??'';
    $result=$TEMPLATES;
    if($category) $result=array_values(array_filter($result,function($t) use($category){return $t['category']===$category;}));
    tp_ok('OK',$result);
}

// Get single template
if($action==='get'){
    $id=$_GET['id']??'';
    foreach($TEMPLATES as $t){if($t['id']===$id){tp_ok('OK',$t);}}
    tp_ok('OK',null);
}

// Fill template with data
if($action==='fill'&&$_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $id=$input['template_id']??'';
    $data=$input['data']??[];
    $tpl=null;
    foreach($TEMPLATES as $t){if($t['id']===$id){$tpl=$t;break;}}
    if(!$tpl) tp_ok('OK',null);

    $content=$tpl['template'];
    foreach($data as $k=>$v) $content=str_replace('{'.$k.'}',$v,$content);
    // Clean unfilled placeholders
    $content=preg_replace('/\{[a-z_]+\}/','',$content);
    $content=trim(preg_replace('/\n{3,}/',"\n\n",$content));

    tp_ok('OK',['content'=>$content,'type'=>$tpl['id']]);
}

// Categories
if($action==='categories'){
    tp_ok('OK',[
        ['id'=>'work','name'=>'Công việc','icon'=>'🛵'],
        ['id'=>'knowledge','name'=>'Kiến thức','icon'=>'📚'],
        ['id'=>'community','name'=>'Cộng đồng','icon'=>'👥'],
        ['id'=>'personal','name'=>'Cá nhân','icon'=>'🏆'],
    ]);
}

tp_ok('OK',[]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
