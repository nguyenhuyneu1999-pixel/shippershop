<?php
// ShipperShop API v2 — Two-Factor Authentication (TOTP)
// Setup, verify, enable/disable 2FA
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

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function tf_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function tf_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// Simple TOTP implementation (no external lib needed)
function generateSecret($length=16){
    $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret='';
    for($i=0;$i<$length;$i++) $secret.=$chars[random_int(0,31)];
    return $secret;
}

function verifyTOTP($secret,$code,$window=1){
    $timeSlice=floor(time()/30);
    for($i=-$window;$i<=$window;$i++){
        $calcCode=getTOTPCode($secret,$timeSlice+$i);
        if(hash_equals($calcCode,str_pad($code,6,'0',STR_PAD_LEFT))) return true;
    }
    return false;
}

function getTOTPCode($secret,$timeSlice){
    // Base32 decode
    $lut=['A'=>0,'B'=>1,'C'=>2,'D'=>3,'E'=>4,'F'=>5,'G'=>6,'H'=>7,'I'=>8,'J'=>9,'K'=>10,'L'=>11,'M'=>12,'N'=>13,'O'=>14,'P'=>15,'Q'=>16,'R'=>17,'S'=>18,'T'=>19,'U'=>20,'V'=>21,'W'=>22,'X'=>23,'Y'=>24,'Z'=>25,'2'=>26,'3'=>27,'4'=>28,'5'=>29,'6'=>30,'7'=>31];
    $b='';
    foreach(str_split(strtoupper($secret)) as $c) $b.=str_pad(decbin($lut[$c]??0),5,'0',STR_PAD_LEFT);
    $key='';
    for($i=0;$i<strlen($b)-7;$i+=8) $key.=chr(bindec(substr($b,$i,8)));

    $time=pack('N*',0).pack('N*',$timeSlice);
    $hash=hash_hmac('sha1',$time,$key,true);
    $offset=ord($hash[19])&0xf;
    $code=(((ord($hash[$offset])&0x7f)<<24)|((ord($hash[$offset+1])&0xff)<<16)|((ord($hash[$offset+2])&0xff)<<8)|(ord($hash[$offset+3])&0xff))%1000000;
    return str_pad($code,6,'0',STR_PAD_LEFT);
}

try {

$uid=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    // Status
    if(!$action||$action==='status'){
        $user=$d->fetchOne("SELECT two_factor_enabled,two_factor_secret FROM users WHERE id=?",[$uid]);
        tf_ok('OK',['enabled'=>intval($user['two_factor_enabled']??0),'has_secret'=>!empty($user['two_factor_secret'])]);
    }
    tf_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $input=json_decode(file_get_contents('php://input'),true);

    // Generate new secret
    if($action==='setup'){
        $secret=generateSecret();
        $d->query("UPDATE users SET two_factor_secret=? WHERE id=?",[$secret,$uid]);
        $user=$d->fetchOne("SELECT email FROM users WHERE id=?",[$uid]);
        $otpUrl='otpauth://totp/ShipperShop:'.urlencode($user['email']??'user').'?secret='.$secret.'&issuer=ShipperShop&digits=6&period=30';
        tf_ok('OK',['secret'=>$secret,'otp_url'=>$otpUrl,'qr_url'=>'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data='.urlencode($otpUrl)]);
    }

    // Verify + enable
    if($action==='verify'){
        rate_enforce('2fa_verify',5,300);
        $code=trim($input['code']??'');
        if(strlen($code)!==6||!ctype_digit($code)) tf_fail('Mã phải 6 chữ số');
        $user=$d->fetchOne("SELECT two_factor_secret FROM users WHERE id=?",[$uid]);
        if(!$user||!$user['two_factor_secret']) tf_fail('Chưa thiết lập 2FA');
        if(!verifyTOTP($user['two_factor_secret'],$code)) tf_fail('Mã không đúng hoặc hết hạn');
        $d->query("UPDATE users SET two_factor_enabled=1 WHERE id=?",[$uid]);
        try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'2fa_enable','2FA enabled',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
        tf_ok('Đã bật xác thực 2 bước!');
    }

    // Disable
    if($action==='disable'){
        rate_enforce('2fa_disable',3,300);
        $code=trim($input['code']??'');
        $password=trim($input['password']??'');
        // Verify password
        $user=$d->fetchOne("SELECT password,two_factor_secret,two_factor_enabled FROM users WHERE id=?",[$uid]);
        if(!$user||!password_verify($password,$user['password'])) tf_fail('Sai mật khẩu');
        if($user['two_factor_enabled']&&$user['two_factor_secret']){
            if(!verifyTOTP($user['two_factor_secret'],$code)) tf_fail('Mã 2FA không đúng');
        }
        $d->query("UPDATE users SET two_factor_enabled=0,two_factor_secret=NULL WHERE id=?",[$uid]);
        try{$pdo->prepare("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'2fa_disable','2FA disabled',?,NOW())")->execute([$uid,$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}
        tf_ok('Đã tắt xác thực 2 bước');
    }

    tf_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
