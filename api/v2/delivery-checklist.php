<?php
// ShipperShop API v2 — Delivery Checklist
// Pre-delivery checklist: vehicle, gear, documents, phone, battery
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

$DEFAULT_ITEMS=[
    ['id'=>1,'category'=>'vehicle','text'=>'Kiem tra xang/sac xe','icon'=>'⛽'],
    ['id'=>2,'category'=>'vehicle','text'=>'Kiem tra lop, phanh','icon'=>'🔧'],
    ['id'=>3,'category'=>'vehicle','text'=>'Den xe hoat dong','icon'=>'💡'],
    ['id'=>4,'category'=>'gear','text'=>'Ao mua','icon'=>'🧥'],
    ['id'=>5,'category'=>'gear','text'=>'Thung/tui giao hang','icon'=>'📦'],
    ['id'=>6,'category'=>'gear','text'=>'Non bao hiem','icon'=>'⛑️'],
    ['id'=>7,'category'=>'docs','text'=>'Bang lai xe','icon'=>'🪪'],
    ['id'=>8,'category'=>'docs','text'=>'Dang ky xe','icon'=>'📋'],
    ['id'=>9,'category'=>'tech','text'=>'Dien thoai day pin','icon'=>'🔋'],
    ['id'=>10,'category'=>'tech','text'=>'4G/WiFi hoat dong','icon'=>'📶'],
    ['id'=>11,'category'=>'tech','text'=>'App hang van chuyen','icon'=>'📱'],
    ['id'=>12,'category'=>'personal','text'=>'Nuoc uong','icon'=>'💧'],
    ['id'=>13,'category'=>'personal','text'=>'Tien le doi tra','icon'=>'💰'],
    ['id'=>14,'category'=>'personal','text'=>'Sac du phong','icon'=>'🔌'],
];

function dcl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}

try {

$uid=require_auth();
$date=$_GET['date']??date('Y-m-d');
$key='checklist_'.$uid.'_'.$date;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $checked=$row?json_decode($row['value'],true):[];
    $items=$DEFAULT_ITEMS;
    foreach($items as &$item){$item['checked']=in_array($item['id'],$checked);}unset($item);
    $total=count($items);$done=count($checked);
    $pct=$total>0?round($done/$total*100):0;
    $ready=$pct>=80;
    $categories=['vehicle'=>'Xe','gear'=>'Trang bi','docs'=>'Giay to','tech'=>'Cong nghe','personal'=>'Ca nhan'];
    dcl_ok('OK',['items'=>$items,'categories'=>$categories,'progress'=>$pct,'checked'=>$done,'total'=>$total,'ready'=>$ready,'date'=>$date]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $checked=$row?json_decode($row['value'],true):[];
    $itemId=intval($input['item_id']??0);
    if(in_array($itemId,$checked)){$checked=array_values(array_diff($checked,[$itemId]));}
    else{$checked[]=$itemId;}
    $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
    if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($checked),$key]);
    else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($checked)]);
    $pct=count($DEFAULT_ITEMS)>0?round(count($checked)/count($DEFAULT_ITEMS)*100):0;
    dcl_ok($pct>=100?'San sang giao hang!':'Da check '.$pct.'%');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
