<?php
// ShipperShop API v2 — Post Boost/Promote
// Pay to boost post visibility, admin approve, track impressions
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

function pb_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pb_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// Boost packages
$PACKAGES=[
    ['id'=>1,'name'=>'Cơ bản','price'=>5000,'duration_hours'=>6,'priority'=>1,'desc'=>'Hiện trên feed 6 giờ'],
    ['id'=>2,'name'=>'Tiêu chuẩn','price'=>15000,'duration_hours'=>24,'priority'=>2,'desc'=>'Hiện trên feed 24 giờ + badge'],
    ['id'=>3,'name'=>'Premium','price'=>30000,'duration_hours'=>72,'priority'=>3,'desc'=>'3 ngày + badge + thông báo followers'],
];

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    // List packages
    if($action==='packages'||!$action) pb_ok('OK',['packages'=>$PACKAGES]);

    // Get boost status for a post
    if($action==='status'){
        $postId=intval($_GET['post_id']??0);
        if(!$postId) pb_fail('Missing post_id');
        $row=$d->fetchOne("SELECT value FROM settings WHERE `key`=?",['boost_'.$postId]);
        if(!$row) pb_ok('OK',['boosted'=>false]);
        $boost=json_decode($row['value'],true);
        $boost['active']=$boost['expires_at']&&strtotime($boost['expires_at'])>time();
        pb_ok('OK',$boost);
    }

    // Admin: list all active boosts
    if($action==='active'){
        $uid=require_auth();
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') pb_fail('Admin only',403);
        $boosts=$d->fetchAll("SELECT `key`,value FROM settings WHERE `key` LIKE 'boost_%'");
        $active=[];
        foreach($boosts as $b){
            $data=json_decode($b['value'],true);
            if($data&&isset($data['expires_at'])&&strtotime($data['expires_at'])>time()){
                $data['post_id']=intval(str_replace('boost_','',$b['key']));
                $active[]=$data;
            }
        }
        pb_ok('OK',$active);
    }

    pb_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Boost a post
    if($action==='boost'||!$action){
        $postId=intval($input['post_id']??0);
        $packageId=intval($input['package_id']??1);
        if(!$postId) pb_fail('Missing post_id');

        // Verify post ownership
        $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=? AND `status`='active'",[$postId]);
        if(!$post||intval($post['user_id'])!==$uid) pb_fail('Không có quyền',403);

        // Find package
        $pkg=null;
        foreach($PACKAGES as $p){if($p['id']===$packageId){$pkg=$p;break;}}
        if(!$pkg) pb_fail('Gói không hợp lệ');

        // Check wallet balance
        $wallet=$d->fetchOne("SELECT balance FROM wallets WHERE user_id=?",[$uid]);
        $balance=intval($wallet['balance']??0);
        if($balance<$pkg['price']) pb_fail('Số dư không đủ. Cần '.number_format($pkg['price']).'đ');

        // Deduct + create boost
        $d->query("UPDATE wallets SET balance=balance-? WHERE user_id=?",[$pkg['price'],$uid]);
        $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,created_at) VALUES (?,'boost',?,?,NOW())")->execute([$uid,-$pkg['price'],'Boost bài #'.$postId.' - '.$pkg['name']]);

        $expiresAt=date('Y-m-d H:i:s',time()+$pkg['duration_hours']*3600);
        $boostData=['boosted'=>true,'package'=>$pkg['name'],'priority'=>$pkg['priority'],'user_id'=>$uid,'started_at'=>date('c'),'expires_at'=>$expiresAt,'impressions'=>0];

        $key='boost_'.$postId;
        $existing=$d->fetchOne("SELECT id FROM settings WHERE `key`=?",[$key]);
        if($existing) $d->query("UPDATE settings SET value=? WHERE `key`=?",[json_encode($boostData),$key]);
        else $d->query("INSERT INTO settings (`key`,value) VALUES (?,?)",[$key,json_encode($boostData)]);

        // Audit
        try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'post_boost',?,?,NOW())")->execute([$uid,'Post #'.$postId.' boosted: '.$pkg['name'],$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

        pb_ok('Đã boost bài viết!',['expires_at'=>$expiresAt,'package'=>$pkg['name']]);
    }

    pb_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
