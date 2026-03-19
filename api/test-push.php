<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';
header('Content-Type: text/plain');

echo "=== Push Helper Test ===\n";
echo "openssl_pkey_derive: ".(function_exists('openssl_pkey_derive')?'YES':'NO')."\n";
echo "openssl_encrypt: ".(function_exists('openssl_encrypt')?'YES':'NO')."\n";
echo "openssl_sign: ".(function_exists('openssl_sign')?'YES':'NO')."\n";
echo "openssl_pkey_new: ".(function_exists('openssl_pkey_new')?'YES':'NO')."\n";

// Test VAPID JWT signing
echo "\n=== Test VAPID JWT ===\n";
$privPem = createPemFromPrivateKey(VAPID_PRIVATE_KEY);
$privKey = openssl_pkey_get_private($privPem);
echo "Private key load: ".($privKey?'OK':'FAIL')."\n";
if(!$privKey){while($e=openssl_error_string())echo "  err: $e\n";}

// Test encrypt payload
echo "\n=== Test Encrypt ===\n";
$testKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
if($testKey){
    $det = openssl_pkey_get_details($testKey);
    $pubKey = chr(4).$det['ec']['x'].$det['ec']['y'];
    $p256dh = base64url_encode($pubKey);
    $auth = base64url_encode(random_bytes(16));
    $result = encryptPayload('test payload', $p256dh, $auth);
    echo "Encrypt result: ".($result?'OK ('.strlen($result['ciphertext']).' bytes)':'FAIL')."\n";
}else{
    echo "Test key gen failed\n";
}

// Test full sendPushNotification with a fake endpoint
echo "\n=== Test Flow ===\n";
$fakeSub = ['endpoint'=>'https://fcm.googleapis.com/fcm/send/fake-test','p256dh'=>$p256dh ?? '','auth'=>$auth ?? ''];
$result = sendPushNotification($fakeSub, json_encode(['title'=>'Test','body'=>'Hello']));
echo "Send result: status=".$result['status']." success=".($result['success']?'yes':'no')."\n";
echo "Response: ".(is_string($result['response'])?substr($result['response'],0,100):'null')."\n";

echo "\n=== DB Check ===\n";
$d=db();
$cnt=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];
echo "Subscriptions in DB: $cnt\n";

echo "\nDONE\n";
