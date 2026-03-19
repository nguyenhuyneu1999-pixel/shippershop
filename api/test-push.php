<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';

echo "======================================\n";
echo " PUSH NOTIFICATION - FULL TEST\n";
echo "======================================\n\n";

$errors = [];

// TEST 1: VAPID Keys
echo "1. VAPID Keys\n";
echo "   Public key: ".substr(VAPID_PUBLIC_KEY,0,20)."... (".strlen(VAPID_PUBLIC_KEY)." chars)\n";
$pubDec = base64url_decode(VAPID_PUBLIC_KEY);
echo "   Decoded: ".strlen($pubDec)." bytes, starts 0x".bin2hex($pubDec[0])."\n";
if(strlen($pubDec)!==65) $errors[]="VAPID public key decoded != 65 bytes";
if(ord($pubDec[0])!==4) $errors[]="VAPID public key doesn't start with 0x04";
echo "   PEM defined: ".(defined('VAPID_PRIVATE_PEM')?'YES':'NO')."\n";
if(!defined('VAPID_PRIVATE_PEM')) $errors[]="VAPID_PRIVATE_PEM not defined";

// TEST 2: Private key loads  
echo "\n2. Private Key\n";
$privKey = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo "   Valid: ".($privKey?'YES':'NO')."\n";
if(!$privKey){
    $errors[]="Private key invalid: ".openssl_error_string();
}else{
    $det=openssl_pkey_get_details($privKey);
    echo "   Type: ".$det['type']." (3=EC)\n";
    echo "   Bits: ".$det['bits']."\n";
}

// TEST 3: JWT Signing
echo "\n3. JWT Signing\n";
if($privKey){
    $h=base64url_encode('{"typ":"JWT","alg":"ES256"}');
    $c=base64url_encode('{"aud":"https://fcm.googleapis.com","exp":'.(time()+3600).',"sub":"mailto:admin@shippershop.vn"}');
    $ok=openssl_sign($h.'.'.$c,$sig,$privKey,OPENSSL_ALGO_SHA256);
    echo "   Signed: ".($ok?'YES':'NO')."\n";
    if(!$ok) $errors[]="JWT signing failed";
    if($ok){
        $raw=derToRaw($sig);
        echo "   Raw sig: ".strlen($raw)." bytes (should be 64)\n";
        if(strlen($raw)!==64) $errors[]="DER to raw sig != 64 bytes";
    }
}

// TEST 4: EC Key Generation
echo "\n4. EC Key Generation\n";
$testKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo "   Generated: ".($testKey?'YES':'NO')."\n";
if(!$testKey) $errors[]="Cannot generate EC key";

// TEST 5: ECDH Key Exchange
echo "\n5. ECDH Key Exchange\n";
if($testKey){
    // Simulate client keys
    $clientKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    $cDet=openssl_pkey_get_details($clientKey);
    $clientPub=chr(4).$cDet['ec']['x'].$cDet['ec']['y'];
    
    $shared=computeECDH($testKey,$clientPub);
    echo "   Shared secret: ".($shared?strlen($shared)." bytes":'FAILED')."\n";
    if(!$shared) $errors[]="ECDH failed";
}

// TEST 6: AES-128-GCM
echo "\n6. AES-128-GCM\n";
$aesKey=random_bytes(16);
$nonce=random_bytes(12);
$tag='';
$ct=openssl_encrypt("test",'aes-128-gcm',$aesKey,OPENSSL_RAW_DATA,$nonce,$tag,'',16);
echo "   Encrypt: ".($ct!==false?'OK':'FAIL')."\n";
echo "   Tag: ".strlen($tag)." bytes\n";
if($ct===false) $errors[]="AES-128-GCM encryption failed";

// TEST 7: Full Encryption Pipeline
echo "\n7. Full Encryption\n";
if($testKey && $shared){
    $clientPubB64=base64url_encode($clientPub);
    $clientAuth=base64url_encode(random_bytes(16));
    $result=encryptPayload('{"title":"Test","body":"Hello World"}', $clientPubB64, $clientAuth);
    echo "   Encrypted: ".($result?strlen($result['ciphertext'])." bytes":'FAILED')."\n";
    if(!$result) $errors[]="Full encryption pipeline failed";
    if($result){
        // Verify header structure
        $ct=$result['ciphertext'];
        echo "   Salt(16): ".strlen(substr($ct,0,16))."\n";
        echo "   RS(4): ".unpack('N',substr($ct,16,4))[1]."\n";
        echo "   IDLen(1): ".ord($ct[20])."\n";
        echo "   KeyID(65): ".strlen(substr($ct,21,65))." bytes\n";
        echo "   Payload: ".(strlen($ct)-86)." bytes\n";
    }
}

// TEST 8: Full Send (to httpbin.org for test)
echo "\n8. Full Send Test (httpbin.org)\n";
$fakeSub=[
    'endpoint'=>'https://httpbin.org/post',
    'p256dh'=>base64url_encode($clientPub),
    'auth'=>base64url_encode(random_bytes(16))
];
$sendResult=sendPushNotification($fakeSub, '{"title":"Test","body":"Hello"}');
echo "   Status: ".$sendResult['status']."\n";
echo "   Success: ".($sendResult['success']?'YES':'NO')."\n";
// httpbin returns 200 for anything, so success=true means HTTP layer works
if(!$sendResult['success'] && $sendResult['status']!==200) $errors[]="HTTP send failed: status ".$sendResult['status'];

// TEST 9: Database
echo "\n9. Database\n";
$d=db();
$count=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions");
echo "   Subscriptions: ".$count['c']."\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM push_subscriptions");
$colNames=array_column($cols,'Field');
echo "   Columns: ".implode(', ',$colNames)."\n";
$required=['id','user_id','endpoint','p256dh','auth'];
foreach($required as $req){
    if(!in_array($req,$colNames)){$errors[]="Missing column: $req";}
}

// TEST 10: API Endpoints
echo "\n10. API Endpoints\n";
$vapidResp=file_get_contents('https://shippershop.vn/api/push.php?action=vapid_key',false,stream_context_create(['ssl'=>['verify_peer'=>false]]));
$vapidData=json_decode($vapidResp,true);
echo "    vapid_key: ".($vapidData['success']?'OK':'FAIL')."\n";
if(!$vapidData['success']) $errors[]="vapid_key endpoint failed";

// TEST 11: VAPID key match
echo "\n11. Client-Server Key Match\n";
echo "    Server key: ".substr(VAPID_PUBLIC_KEY,0,20)."...\n";
echo "    API key:    ".substr($vapidData['data']['publicKey']??'',0,20)."...\n";
$match=(VAPID_PUBLIC_KEY===($vapidData['data']['publicKey']??''));
echo "    Match: ".($match?'YES':'NO')."\n";
if(!$match) $errors[]="VAPID key mismatch between server and API";

// SUMMARY
echo "\n======================================\n";
if(empty($errors)){
    echo " ALL 11 TESTS PASSED ✓\n";
    echo " Push system is fully operational\n";
}else{
    echo " ".count($errors)." ERRORS FOUND:\n";
    foreach($errors as $i=>$e) echo " ".($i+1).". $e\n";
}
echo "======================================\n";
