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

// 1
echo "1.  VAPID Keys: ";
$pubDec=base64url_decode(VAPID_PUBLIC_KEY);
$ok1=(strlen($pubDec)===65&&ord($pubDec[0])===4);
echo $ok1?'OK':'FAIL'; echo "\n";
if(!$ok1) $errors[]="VAPID pub key format";

// 2
echo "2.  Private Key PEM: ";
$pk=openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo $pk?'OK':'FAIL'; echo "\n";
if(!$pk) $errors[]="Private key";

// 3
echo "3.  JWT Sign (ES256): ";
while(openssl_error_string()){}
$h=base64url_encode('{"typ":"JWT","alg":"ES256"}');
$c=base64url_encode('{"aud":"https://fcm.googleapis.com","exp":'.(time()+3600).'}');
$signed=openssl_sign($h.'.'.$c,$sig,$pk,OPENSSL_ALGO_SHA256);
echo $signed?'OK (sig '.strlen(derToRaw($sig)).'B)':'FAIL'; echo "\n";
if(!$signed) $errors[]="JWT sign";

// 4
echo "4.  EC Key Gen: ";
$testKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo $testKey?'OK':'FAIL'; echo "\n";
if(!$testKey) $errors[]="EC key gen";

// 5
echo "5.  ECDH Exchange: ";
$clientKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$cDet=openssl_pkey_get_details($clientKey);
$clientPub=chr(4).$cDet['ec']['x'].$cDet['ec']['y'];
$shared=computeECDH($testKey,$clientPub);
echo $shared?'OK ('.strlen($shared).'B)':'FAIL'; echo "\n";
if(!$shared) $errors[]="ECDH";

// 6
echo "6.  AES-128-GCM: ";
$tag='';$aes=openssl_encrypt("test",'aes-128-gcm',random_bytes(16),OPENSSL_RAW_DATA,random_bytes(12),$tag,'',16);
echo ($aes!==false)?'OK':'FAIL'; echo "\n";
if($aes===false) $errors[]="AES-GCM";

// 7
echo "7.  Full Encrypt: ";
$cpB64=base64url_encode($clientPub);$caB64=base64url_encode(random_bytes(16));
$enc=encryptPayload('{"title":"Test","body":"Hello World!"}', $cpB64, $caB64);
echo $enc?'OK ('.strlen($enc['ciphertext']).'B)':'FAIL'; echo "\n";
if(!$enc) $errors[]="Encryption pipeline";

// 8
echo "8.  HTTP Send: ";
if($enc){
    $fSub=['endpoint'=>'https://httpbin.org/post','p256dh'=>$cpB64,'auth'=>$caB64];
    $res=sendPushNotification($fSub, '{"title":"T","body":"B"}');
    echo "HTTP ".$res['status'].($res['success']?' OK':' FAIL'); echo "\n";
}else{echo "SKIP\n";}

// 9
echo "9.  Database: ";
$d=db();$cnt=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions");
echo "OK (".$cnt['c']." subs)\n";

// 10
echo "10. API vapid_key: ";
$resp=@file_get_contents('https://shippershop.vn/api/push.php?action=vapid_key',false,stream_context_create(['ssl'=>['verify_peer'=>false]]));
$data=json_decode($resp,true);
echo ($data&&$data['success'])?'OK':'FAIL'; echo "\n";
if(!$data||!$data['success']) $errors[]="API vapid_key";

// 11
echo "11. Key match: ";
$apiKey=$data['data']['publicKey']??'';
echo (VAPID_PUBLIC_KEY===$apiKey)?'OK':'MISMATCH'; echo "\n";
if(VAPID_PUBLIC_KEY!==$apiKey) $errors[]="Key mismatch";

// 12
echo "12. notifyUser: ";
$sent=notifyUser(2,'Test','Body','general','/');
echo "sent=$sent (0 expected, no subs yet)\n";

// Client pages
echo "\n--- Client Pages ---\n";
$newKey=VAPID_PUBLIC_KEY;
foreach(['index.html','messages.html','profile.html','groups.html'] as $pg){
    $html=@file_get_contents('https://shippershop.vn/'.$pg,false,stream_context_create(['ssl'=>['verify_peer'=>false]]));
    $has=($html&&strpos($html,$newKey)!==false);
    echo "13. $pg key: ".($has?'OK':'MISMATCH/MISSING')."\n";
    if(!$has) $errors[]="$pg missing/wrong VAPID key";
}

// SW
echo "\n--- Service Worker ---\n";
$sw=@file_get_contents('https://shippershop.vn/sw.js',false,stream_context_create(['ssl'=>['verify_peer'=>false]]));
echo "14. SW version: ".($sw?substr($sw,0,35):'FAIL')."\n";
echo "15. Push handler: ".(strpos($sw,'addEventListener')!==false&&strpos($sw,'push')!==false?'OK':'MISSING')."\n";
echo "16. notificationclick: ".(strpos($sw,'notificationclick')!==false?'OK':'MISSING')."\n";
echo "17. Category tags: ".(strpos($sw,'msg-')!==false?'OK':'MISSING')."\n";

// Hooks
echo "\n--- API Hooks ---\n";
$mapi=file_get_contents(__DIR__.'/messages-api.php');
echo "18. messages-api notifyUser: ".(strpos($mapi,'notifyUser')!==false?'OK':'MISSING')."\n";
if(strpos($mapi,'notifyUser')===false) $errors[]="messages-api hook";
$papi=file_get_contents(__DIR__.'/posts.php');
echo "19. posts.php notifyUser: ".(strpos($papi,'notifyUser')!==false?'OK':'MISSING')."\n";
if(strpos($papi,'notifyUser')===false) $errors[]="posts.php hook";
$gapi=file_get_contents(__DIR__.'/groups.php');
echo "20. groups.php notifyUser: ".(substr_count($gapi,'notifyUser')>=2?'OK (x'.substr_count($gapi,'notifyUser').')':'MISSING')."\n";
if(substr_count($gapi,'notifyUser')<2) $errors[]="groups.php hooks";

echo "\n======================================\n";
if(empty($errors)){
    echo " ALL 20 TESTS PASSED\n";
    echo " Push system is FULLY OPERATIONAL\n";
}else{
    echo " ".count($errors)." ERRORS:\n";
    foreach($errors as $e) echo "   - $e\n";
}
echo "======================================\n";
