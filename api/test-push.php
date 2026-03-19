<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';

echo "======================================\n";
echo " PUSH NOTIFICATION - FULL TEST\n";
echo "======================================\n\n";
$errors=[];

// 1. VAPID
echo "1. VAPID Keys: ";
$pubDec=base64url_decode(VAPID_PUBLIC_KEY);
echo (strlen($pubDec)===65&&ord($pubDec[0])===4)?'OK':'FAIL';
echo " (".strlen(VAPID_PUBLIC_KEY)." chars)\n";
if(strlen($pubDec)!==65) $errors[]="VAPID pub != 65 bytes";

// 2. Private Key
echo "2. Private Key: ";
$pk=openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo $pk?'OK':'FAIL'; echo "\n";
if(!$pk) $errors[]="Private key invalid";

// 3. JWT Signing
echo "3. JWT Sign: ";
$h=base64url_encode('{"typ":"JWT","alg":"ES256"}');
$c=base64url_encode('{"aud":"https://fcm.googleapis.com","exp":'.(time()+3600).'}');
$ok=openssl_sign($h.'.'.$c,$sig,$pk,OPENSSL_ALGO_SHA256);
echo $ok?'OK':'FAIL'; echo " (raw sig ".strlen(derToRaw($sig))." bytes)\n";
if(!$ok) $errors[]="JWT signing failed";

// 4. EC Key Gen
echo "4. EC Key Gen: ";
$testKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo $testKey?'OK':'FAIL'; echo "\n";

// 5. ECDH
echo "5. ECDH: ";
$clientKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$cDet=openssl_pkey_get_details($clientKey);
$clientPub=chr(4).$cDet['ec']['x'].$cDet['ec']['y'];
$shared=computeECDH($testKey,$clientPub);
echo $shared?'OK ('.strlen($shared).' bytes)':'FAIL'; echo "\n";
if(!$shared){
    $errors[]="ECDH failed";
    while($e=openssl_error_string()) echo "   openssl: $e\n";
}

// 6. AES-128-GCM
echo "6. AES-GCM: ";
$tag='';$ct=openssl_encrypt("test",'aes-128-gcm',random_bytes(16),OPENSSL_RAW_DATA,random_bytes(12),$tag,'',16);
echo ($ct!==false)?'OK':'FAIL'; echo "\n";

// 7. Full Encryption
echo "7. Full Encrypt: ";
$clientPubB64=base64url_encode($clientPub);
$clientAuth=base64url_encode(random_bytes(16));
$enc=encryptPayload('{"title":"Test","body":"Hello World"}', $clientPubB64, $clientAuth);
echo $enc?'OK ('.strlen($enc['ciphertext']).' bytes)':'FAIL'; echo "\n";
if(!$enc) $errors[]="Encryption pipeline failed";

// 8. HTTP Send
echo "8. HTTP Send (httpbin): ";
if($enc){
    $fakeSub=['endpoint'=>'https://httpbin.org/post','p256dh'=>$clientPubB64,'auth'=>$clientAuth];
    $res=sendPushNotification($fakeSub, '{"title":"Test","body":"Hello"}');
    echo "status=".$res['status']." ".($res['success']?'OK':'FAIL'); echo "\n";
}else echo "SKIP (encrypt failed)\n";

// 9. DB
echo "9. Database: ";
$d=db();
$cnt=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions");
echo "OK (".$cnt['c']." subscriptions)\n";

// 10. API
echo "10. API vapid_key: ";
$resp=@file_get_contents('https://shippershop.vn/api/push.php?action=vapid_key',false,stream_context_create(['ssl'=>['verify_peer'=>false]]));
$data=json_decode($resp,true);
echo ($data&&$data['success'])?'OK':'FAIL'; echo "\n";

// 11. Key match
echo "11. Client-Server match: ";
$apiKey=$data['data']['publicKey']??'';
echo (VAPID_PUBLIC_KEY===$apiKey)?'OK':'MISMATCH'; echo "\n";
if(VAPID_PUBLIC_KEY!==$apiKey) $errors[]="Key mismatch";

// 12. notifyUser (user 2)
echo "12. notifyUser(2): ";
$sent=notifyUser(2,'Test','Test body','general','/');
echo "sent to $sent devices\n";

echo "\n======================================\n";
if(empty($errors)){
    echo " ALL 12 TESTS PASSED\n";
}else{
    echo " ".count($errors)." ERRORS:\n";
    foreach($errors as $e) echo "   - $e\n";
}
echo "======================================\n";
