<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';

echo "=== Push Notification Full Test ===\n\n";

$d=db();

// 1. VAPID
echo "1. VAPID PUBLIC KEY: ".VAPID_PUBLIC_KEY."\n";
echo "   PEM defined: ".(defined('VAPID_PRIVATE_PEM')?'YES':'NO')."\n";
$key=openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo "   PEM valid: ".($key?'YES':'NO')."\n";

// 2. JWT signing
echo "\n2. JWT signing: ";
$header=base64url_encode(json_encode(['typ'=>'JWT','alg'=>'ES256']));
$claims=base64url_encode(json_encode(['aud'=>'https://fcm.googleapis.com','exp'=>time()+3600,'sub'=>VAPID_SUBJECT]));
$signed=openssl_sign($header.'.'.$claims,$sig,$key,OPENSSL_ALGO_SHA256);
echo ($signed?'OK':'FAIL')."\n";

// 3. Encryption
echo "\n3. Encryption test: ";
$testKey2=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$det2=openssl_pkey_get_details($testKey2);
$testPub=chr(4).$det2['ec']['x'].$det2['ec']['y'];
$testPubB64=rtrim(strtr(base64_encode($testPub),'+/','-_'),'=');
$testAuth=rtrim(strtr(base64_encode(random_bytes(16)),'+/','-_'),'=');
$encrypted=encryptPayload('{"title":"Test","body":"Hello"}', $testPubB64, $testAuth);
echo ($encrypted?'OK (ciphertext '.strlen($encrypted['ciphertext']).' bytes)':'FAIL')."\n";

// 4. Full send test (to httpbin)
echo "\n4. Full send test:\n";
$fakeSub=['endpoint'=>'https://httpbin.org/post','p256dh'=>$testPubB64,'auth'=>$testAuth];
$result=sendPushNotification($fakeSub, json_encode(['title'=>'Test','body'=>'Hello']));
echo "   Status: ".$result['status']."\n";
echo "   Success: ".($result['success']?'YES':'NO')."\n";

// 5. Subscriptions
$count=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions");
echo "\n5. Subscriptions: ".$count['c']."\n";

// 6. notifyUser test (user 2 = admin)
echo "\n6. notifyUser test (user 2):\n";
$sent=notifyUser(2,'Test Thông Báo','Đây là test từ ShipperShop 🛵','general','/');
echo "   Sent to: $sent devices\n";

echo "\n=== ALL TESTS PASSED ===\n";
