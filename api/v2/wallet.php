<?php
/**
 * ShipperShop API v2 — Wallet (thin wrapper fixing getPdo bug + transfer)
 * Delegates to wallet-api.php for existing endpoints, adds new ones
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';
require_once __DIR__.'/../../includes/validator.php';

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

// GET: Plans (public) — also default for bare GET
if($_SERVER['REQUEST_METHOD']==='GET'&&($action==='plans'||!$action)){
    $plans=$d->fetchAll("SELECT * FROM subscription_plans ORDER BY price ASC");
    ok('OK',$plans);
}

// GET: Info (requires auth)
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='info'){
    $uid=require_auth();
    $wallet=$d->fetchOne("SELECT * FROM wallets WHERE user_id=?",[$uid]);
    if(!$wallet){
        $pdo->prepare("INSERT IGNORE INTO wallets (user_id,balance,created_at) VALUES (?,0,NOW())")->execute([$uid]);
        $wallet=['user_id'=>$uid,'balance'=>0];
    }
    $sub=$d->fetchOne("SELECT us.*,sp.name as plan_name,sp.badge FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.expires_at>NOW() ORDER BY us.id DESC LIMIT 1",[$uid]);
    $txns=$d->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10",[$uid]);
    ok('OK',['balance'=>floatval($wallet['balance']??0),'locked_until'=>$wallet['locked_until']??null,'subscription'=>$sub?:null,'recent_transactions'=>$txns]);
}

// GET: Transactions (paginated)
if($_SERVER['REQUEST_METHOD']==='GET'&&$action==='transactions'){
    $uid=require_auth();
    $page=max(1,intval($_GET['page']??1));$limit=20;$offset=($page-1)*$limit;
    $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM wallet_transactions WHERE user_id=?",[$uid])['c']);
    $txns=$d->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT $limit OFFSET $offset",[$uid]);
    echo json_encode(['success'=>true,'data'=>['transactions'=>$txns,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
}

// POST actions
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Transfer between users
    if($action==='transfer'){
        rate_enforce('wallet_transfer',5,3600);
        $toId=intval($input['to_user_id']??0);
        $amount=floatval($input['amount']??0);
        $pin=trim($input['pin']??'');
        if(!$toId||$amount<=0) fail('Thông tin không hợp lệ');
        if($amount<1000) fail('Tối thiểu 1.000đ');
        if($toId===$uid) fail('Không thể chuyển cho chính mình');

        // Verify PIN
        $wallet=$d->fetchOne("SELECT balance,pin_hash,locked_until FROM wallets WHERE user_id=?",[$uid]);
        if(!$wallet) fail('Ví không tồn tại');
        if($wallet['locked_until']&&strtotime($wallet['locked_until'])>time()) fail('Ví đang bị khóa');
        if(!$pin||!password_verify($pin,$wallet['pin_hash']??'')) fail('PIN không đúng');
        if(floatval($wallet['balance'])<$amount) fail('Số dư không đủ');

        // Verify receiver exists
        $toUser=$d->fetchOne("SELECT id,fullname FROM users WHERE id=? AND `status`='active'",[$toId]);
        if(!$toUser) fail('Người nhận không tồn tại');

        // Transaction with lock
        try{
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE wallets SET balance=balance-? WHERE user_id=? AND balance>=?")->execute([$amount,$uid,$amount]);
            if($pdo->prepare("SELECT ROW_COUNT()")->execute()&&$pdo->fetchColumn()==0){$pdo->rollBack();fail('Số dư không đủ');}
            $pdo->prepare("UPDATE wallets SET balance=balance+? WHERE user_id=?")->execute([$amount,$toId]);
            // Ensure receiver wallet exists
            if($pdo->rowCount()===0){
                $pdo->prepare("INSERT INTO wallets (user_id,balance,created_at) VALUES (?,?,NOW())")->execute([$toId,$amount]);
            }
            $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,`status`,created_at) VALUES (?,'transfer_out',?,?,'completed',NOW())")->execute([$uid,$amount,'Chuyển cho '.$toUser['fullname']]);
            $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,`status`,created_at) VALUES (?,'transfer_in',?,?,'completed',NOW())")->execute([$toId,$amount,'Nhận từ chuyển khoản']);
            $pdo->commit();
        }catch(\Throwable $e){
            $pdo->rollBack();
            fail('Lỗi giao dịch: '.$e->getMessage());
        }

        try{$d->query("INSERT INTO audit_log (user_id,action,details,created_at) VALUES (?,'transfer',?,NOW())",[$uid,json_encode(['to'=>$toId,'amount'=>$amount])]);}catch(\Throwable $e){}
        ok('Chuyển thành công!',['amount'=>$amount,'to_user'=>$toUser['fullname']]);
    }

    // Deposit request
    if($action==='deposit'){
        rate_enforce('wallet_deposit',3,3600);
        $amount=floatval($input['amount']??0);
        if($amount<10000) fail('Tối thiểu 10.000đ');
        $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,`status`,created_at) VALUES (?,'deposit',?,'Yêu cầu nạp tiền','pending',NOW())")->execute([$uid,$amount]);
        ok('Đã gửi yêu cầu nạp tiền. Admin sẽ duyệt sớm.');
    }

    // Set/change PIN
    if($action==='set_pin'){
        $pin=trim($input['pin']??'');
        $oldPin=trim($input['old_pin']??'');
        if(strlen($pin)<4||strlen($pin)>6||!ctype_digit($pin)) fail('PIN phải là 4-6 số');
        $wallet=$d->fetchOne("SELECT pin_hash FROM wallets WHERE user_id=?",[$uid]);
        if(!$wallet){$pdo->prepare("INSERT INTO wallets (user_id,balance,created_at) VALUES (?,0,NOW())")->execute([$uid]);$wallet=['pin_hash'=>null];}
        if($wallet['pin_hash']&&!password_verify($oldPin,$wallet['pin_hash'])) fail('PIN cũ không đúng');
        $hash=password_hash($pin,PASSWORD_BCRYPT,['cost'=>12]);
        $d->query("UPDATE wallets SET pin_hash=? WHERE user_id=?",[$hash,$uid]);
        ok('Đã cập nhật PIN');
    }

    // For subscribe, cancel_subscription → delegate to original wallet-api.php
    // (those endpoints are complex with CSRF, we keep original logic)
    fail('Action không hợp lệ. Dùng /api/wallet-api.php cho subscribe/cancel.');
}

fail('Method không hỗ trợ',405);
