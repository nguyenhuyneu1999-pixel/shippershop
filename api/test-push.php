<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';

echo "=== Push System Test ===\n\n";

// Check DB table
$d=db();
$count=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions");
echo "1. Subscriptions in DB: ".$count['c']."\n";

if(intval($count['c'])>0){
    $subs=$d->fetchAll("SELECT id,user_id,LEFT(endpoint,60) as ep,LENGTH(p256dh) as p_len,LENGTH(auth) as a_len,created_at FROM push_subscriptions ORDER BY id DESC LIMIT 5");
    foreach($subs as $s){
        echo "   id=".$s['id']." user=".$s['user_id']." p256dh_len=".$s['p_len']." auth_len=".$s['a_len']." ep=".$s['ep']."...\n";
    }
}

// Check VAPID key format
echo "\n2. VAPID Public Key: ".VAPID_PUBLIC_KEY."\n";
echo "   Key length: ".strlen(VAPID_PUBLIC_KEY)."\n";
$decoded=base64url_decode(VAPID_PUBLIC_KEY);
echo "   Decoded bytes: ".strlen($decoded)."\n";
echo "   Starts with 0x04: ".(ord($decoded[0])===4?'YES':'NO ('.ord($decoded[0]).')')."\n";

echo "\n3. VAPID Private Key length: ".strlen(VAPID_PRIVATE_KEY)."\n";
$privDecoded=base64url_decode(VAPID_PRIVATE_KEY);
echo "   Decoded bytes: ".strlen($privDecoded)." (should be 32)\n";

// Test PEM creation
echo "\n4. PEM key test:\n";
$pem=createPemFromPrivateKey(VAPID_PRIVATE_KEY);
$key=openssl_pkey_get_private($pem);
echo "   Key valid: ".($key?'YES':'NO - '.openssl_error_string())."\n";
if($key){
    $det=openssl_pkey_get_details($key);
    echo "   Type: ".$det['type']." (should be 3=EC)\n";
    echo "   Bits: ".$det['bits']."\n";
}

// Test JWT signing
echo "\n5. JWT signing test:\n";
$header=base64url_encode(json_encode(['typ'=>'JWT','alg'=>'ES256']));
$claims=base64url_encode(json_encode(['aud'=>'https://fcm.googleapis.com','exp'=>time()+3600,'sub'=>VAPID_SUBJECT]));
$sigInput=$header.'.'.$claims;
$signed=openssl_sign($sigInput,$sig,$key,OPENSSL_ALGO_SHA256);
echo "   Signed: ".($signed?'YES':'NO - '.openssl_error_string())."\n";
if($signed){
    $raw=derToRaw($sig);
    echo "   Raw sig length: ".strlen($raw)." (should be 64)\n";
}

// Test encryption
echo "\n6. Encryption test:\n";
echo "   openssl_pkey_new (EC): ";
$testKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo ($testKey?'OK':'FAIL')."\n";
echo "   openssl_pkey_derive available: ".(function_exists('openssl_pkey_derive')?'YES':'NO')."\n";
echo "   aes-128-gcm: ".(in_array('aes-128-gcm',openssl_get_cipher_methods())?'YES':'NO')."\n";

// Test with fake subscription
echo "\n7. End-to-end test (fake sub):\n";
$fakeSub=['endpoint'=>'https://httpbin.org/post','p256dh'=>VAPID_PUBLIC_KEY,'auth'=>base64url_encode(random_bytes(16))];
$result=sendPushNotification($fakeSub, json_encode(['title'=>'Test','body'=>'Hello']));
echo "   Result: ".json_encode($result)."\n";

echo "\nDONE\n";
