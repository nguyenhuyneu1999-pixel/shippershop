<?php
// ShipperShop API v2 — Saved Replies
// Quick reply templates for messages and comments
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

function sr_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function sr_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$uid=require_auth();
$key='saved_replies_'.$uid;

if($_SERVER['REQUEST_METHOD']==='GET'){
    $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
    $replies=$row?json_decode($row['value'],true):[];
    // Add defaults if empty
    if(!$replies){
        $replies=[
            ['id'=>1,'title'=>'Cảm ơn','text'=>'Cảm ơn bạn! 🙏','category'=>'general'],
            ['id'=>2,'title'=>'Đang giao','text'=>'Đơn đang trên đường giao đến bạn. Vui lòng chờ nhé! 📦','category'=>'delivery'],
            ['id'=>3,'title'=>'Đã nhận','text'=>'Đã nhận đơn. Sẽ giao trong hôm nay! ✅','category'=>'delivery'],
            ['id'=>4,'title'=>'Liên hệ','text'=>'Vui lòng liên hệ số điện thoại trên hồ sơ để biết thêm chi tiết.','category'=>'general'],
            ['id'=>5,'title'=>'Chờ xác nhận','text'=>'Đang chờ xác nhận từ hãng vận chuyển. Sẽ cập nhật sớm nhất!','category'=>'delivery'],
        ];
    }
    sr_ok('OK',$replies);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);
    $action=$_GET['action']??'';

    // Add reply
    if(!$action||$action==='add'){
        $title=trim($input['title']??'');
        $text=trim($input['text']??'');
        $category=$input['category']??'general';
        if(!$title||!$text) sr_fail('Nhập tiêu đề và nội dung');

        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $replies=$row?json_decode($row['value'],true):[];
        $maxId=0;foreach($replies as $r){if(($r['id']??0)>$maxId)$maxId=$r['id'];}
        $replies[]=['id'=>$maxId+1,'title'=>$title,'text'=>$text,'category'=>$category];

        if(count($replies)>50) sr_fail('Tối đa 50 mẫu trả lời');
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($replies),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($replies)]);
        sr_ok('Đã thêm',['id'=>$maxId+1]);
    }

    // Delete reply
    if($action==='delete'){
        $replyId=intval($input['reply_id']??0);
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $replies=$row?json_decode($row['value'],true):[];
        $replies=array_values(array_filter($replies,function($r) use($replyId){return ($r['id']??0)!==$replyId;}));
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($replies),$key]);
        sr_ok('Đã xóa');
    }

    // Reorder
    if($action==='reorder'){
        $order=$input['order']??[];
        if(!is_array($order)) sr_fail('Invalid order');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",[$key]);
        $replies=$row?json_decode($row['value'],true):[];
        $map=[];foreach($replies as $r){$map[$r['id']??0]=$r;}
        $sorted=[];foreach($order as $id){if(isset($map[intval($id)]))$sorted[]=$map[intval($id)];}
        $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($sorted),$key]);
        sr_ok('Đã sắp xếp');
    }

    sr_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
