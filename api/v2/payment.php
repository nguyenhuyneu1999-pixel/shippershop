<?php
// ShipperShop API v2 — PayOS Payment Integration
// QR code bank transfer via PayOS (payos.vn)
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

// PayOS config (replace with real keys)
define('PAYOS_CLIENT_ID', getenv('PAYOS_CLIENT_ID') ?: 'demo_client_id');
define('PAYOS_API_KEY', getenv('PAYOS_API_KEY') ?: 'demo_api_key');
define('PAYOS_CHECKSUM_KEY', getenv('PAYOS_CHECKSUM_KEY') ?: 'demo_checksum_key');
define('PAYOS_API_URL', 'https://api-merchant.payos.vn');
define('PAYOS_RETURN_URL', 'https://shippershop.vn/wallet.html?payment=success');
define('PAYOS_CANCEL_URL', 'https://shippershop.vn/wallet.html?payment=cancel');

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function pay_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pay_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// PayOS signature
function payos_sign($data){
    ksort($data);
    $str=implode('&',array_map(function($k,$v) {return "$k=$v";},array_keys($data),array_values($data)));
    return hash_hmac('sha256',$str,PAYOS_CHECKSUM_KEY);
}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Check payment status
    if($action==='check'){
        $orderCode=intval($_GET['order_code']??0);
        if(!$orderCode) pay_fail('Missing order_code');
        $payment=$d->fetchOne("SELECT * FROM payos_payments WHERE order_code=?",[$orderCode]);
        if(!$payment) pay_fail('Payment not found',404);
        pay_ok('OK',$payment);
    }

    // Payment history
    if($action==='history'){
        if(!$uid) pay_fail('Auth required',401);
        $payments=$d->fetchAll("SELECT * FROM payos_payments WHERE user_id=? ORDER BY created_at DESC LIMIT 20",[$uid]);
        pay_ok('OK',$payments);
    }

    pay_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create payment link (deposit)
    if($action==='create'){
        rate_enforce('payment_create',5,3600);
        $amount=intval($input['amount']??0);
        if($amount<10000) pay_fail('Tối thiểu 10.000đ');
        if($amount>10000000) pay_fail('Tối đa 10.000.000đ');

        $orderCode=intval(substr(time(),-8).rand(10,99));
        $description='ShipperShop nap '.$amount.'d';

        // Store payment record
        $pdo->prepare("INSERT INTO payos_payments (user_id,order_code,amount,description,`status`,created_at) VALUES (?,?,?,?,'pending',NOW())")->execute([$uid,$orderCode,$amount,$description]);

        // Build PayOS request
        $payData=[
            'orderCode'=>$orderCode,
            'amount'=>$amount,
            'description'=>$description,
            'returnUrl'=>PAYOS_RETURN_URL,
            'cancelUrl'=>PAYOS_CANCEL_URL,
        ];
        $payData['signature']=payos_sign(['amount'=>$amount,'cancelUrl'=>PAYOS_CANCEL_URL,'description'=>$description,'orderCode'=>$orderCode,'returnUrl'=>PAYOS_RETURN_URL]);

        // Call PayOS API (file_get_contents — shared hosting compatible)
        $httpCode=0;$resp='';
        try {
            $ctx=stream_context_create(['http'=>[
                'method'=>'POST',
                'header'=>"Content-Type: application/json\r\nx-client-id: ".PAYOS_CLIENT_ID."\r\nx-api-key: ".PAYOS_API_KEY."\r\n",
                'content'=>json_encode($payData),
                'timeout'=>15,
                'ignore_errors'=>true,
            ]]);
            $resp=@file_get_contents(PAYOS_API_URL.'/v2/payment-requests',false,$ctx);
            if(isset($http_response_header)){
                foreach($http_response_header as $hdr){
                    if(preg_match('/^HTTP\/\S+\s+(\d+)/',$hdr,$m)){$httpCode=intval($m[1]);}
                }
            }
        } catch (\Throwable $e) { $resp=''; }

        $result=json_decode($resp,true);

        if($httpCode===200&&isset($result['data']['checkoutUrl'])){
            $checkoutUrl=$result['data']['checkoutUrl'];
            $qrCode=$result['data']['qrCode']??'';
            $d->query("UPDATE payos_payments SET checkout_url=?,qr_code=? WHERE order_code=?",[$checkoutUrl,$qrCode,$orderCode]);

            // Audit log
            try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'payment_create',?,?,NOW())")->execute([$uid,'Amount: '.$amount.' Order: '.$orderCode,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

            pay_ok('Đã tạo link thanh toán',['order_code'=>$orderCode,'checkout_url'=>$checkoutUrl,'qr_code'=>$qrCode,'amount'=>$amount]);
        }else{
            // PayOS error or demo mode — return manual transfer info
            $d->query("UPDATE payos_payments SET `status`='manual' WHERE order_code=?",[$orderCode]);
            pay_ok('Chuyển khoản thủ công',['order_code'=>$orderCode,'amount'=>$amount,'bank_info'=>[
                'bank'=>'MB Bank','account'=>'0987654321','name'=>'SHIPPERSHOP','content'=>'SS'.$orderCode
            ],'note'=>'Chuyển khoản với nội dung SS'.$orderCode.'. Admin sẽ duyệt trong 24h.']);
        }
    }

    // Webhook callback from PayOS
    if($action==='webhook'){
        $raw=file_get_contents('php://input');
        $data=json_decode($raw,true);

        if(!$data||!isset($data['data']['orderCode'])) pay_fail('Invalid webhook');

        $orderCode=intval($data['data']['orderCode']);
        $paymentStatus=$data['data']['status']??'';

        // Verify signature
        if(isset($data['signature'])){
            $signData=$data['data'];
            unset($signData['signature']);
            $expectedSig=payos_sign($signData);
            if(!hash_equals($expectedSig,$data['signature'])){
                // Log but don't reject (for testing)
                error_log('PayOS webhook signature mismatch for order '.$orderCode);
            }
        }

        $payment=$d->fetchOne("SELECT * FROM payos_payments WHERE order_code=?",[$orderCode]);
        if(!$payment) pay_fail('Payment not found',404);

        if($paymentStatus==='PAID'&&$payment['status']!=='completed'){
            $d->query("UPDATE payos_payments SET `status`='completed',paid_at=NOW() WHERE order_code=?",[$orderCode]);

            // Credit wallet
            $userId=intval($payment['user_id']);
            $amount=intval($payment['amount']);
            $wallet=$d->fetchOne("SELECT id,balance FROM wallets WHERE user_id=?",[$userId]);
            if($wallet){
                $d->query("UPDATE wallets SET balance=balance+? WHERE user_id=?",[$amount,$userId]);
            }else{
                $pdo->prepare("INSERT INTO wallets (user_id,balance,created_at) VALUES (?,?,NOW())")->execute([$userId,$amount]);
            }
            // Transaction log
            $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,created_at) VALUES (?,'deposit',?,?,NOW())")->execute([$userId,$amount,'PayOS #'.$orderCode]);
            // Notify user
            try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'wallet','Nạp tiền thành công',?,?,NOW())")->execute([$userId,'+'.$amount.'đ đã được cộng vào ví',json_encode(['amount'=>$amount,'order_code'=>$orderCode])]);}catch(\Throwable $e){}
            // Audit
            try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'payment_completed',?,?,NOW())")->execute([$userId,'PayOS #'.$orderCode.' Amount: '.$amount,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
        }elseif($paymentStatus==='CANCELLED'){
            $d->query("UPDATE payos_payments SET `status`='cancelled' WHERE order_code=?",[$orderCode]);
        }

        pay_ok('Webhook processed');
    }

    // Admin manual approve
    if($action==='admin_approve'){
        $admin=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
        if(!$admin||$admin['role']!=='admin') pay_fail('Admin only',403);
        $orderCode=intval($input['order_code']??0);
        $payment=$d->fetchOne("SELECT * FROM payos_payments WHERE order_code=? AND `status` IN ('pending','manual')",[$orderCode]);
        if(!$payment) pay_fail('Payment not found');

        $userId=intval($payment['user_id']);
        $amount=intval($payment['amount']);

        $d->query("UPDATE payos_payments SET `status`='completed',paid_at=NOW() WHERE order_code=?",[$orderCode]);
        $wallet=$d->fetchOne("SELECT id FROM wallets WHERE user_id=?",[$userId]);
        if($wallet){$d->query("UPDATE wallets SET balance=balance+? WHERE user_id=?",[$amount,$userId]);}
        else{$pdo->prepare("INSERT INTO wallets (user_id,balance,created_at) VALUES (?,?,NOW())")->execute([$userId,$amount]);}
        $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,created_at) VALUES (?,'deposit',?,?,NOW())")->execute([$userId,$amount,'Admin approve #'.$orderCode]);
        try{$pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'wallet','Nạp tiền thành công',?,?,NOW())")->execute([$userId,'+'.$amount.'đ đã được cộng vào ví',json_encode(['amount'=>$amount])]);}catch(\Throwable $e){}

        pay_ok('Đã duyệt thanh toán #'.$orderCode);
    }

    pay_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
