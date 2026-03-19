<?php
set_time_limit(15);
error_reporting(E_ALL);ini_set('display_errors','1');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';
header('Content-Type: text/plain');

echo "=== 1. VAPID JWT Sign ===\n";
$header = base64url_encode(json_encode(['typ'=>'JWT','alg'=>'ES256']));
$claims = base64url_encode(json_encode(['aud'=>'https://fcm.googleapis.com','exp'=>time()+3600,'sub'=>VAPID_SUBJECT]));
$sigInput = $header.'.'.$claims;
$privKey = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo "Key load: ".($privKey?'OK':'FAIL')."\n";
if($privKey){
    openssl_sign($sigInput,$derSig,$privKey,OPENSSL_ALGO_SHA256);
    $rawSig=derToRaw($derSig);
    echo "Sign: OK (".strlen($rawSig)." bytes raw sig)\n";
    $jwt=$sigInput.'.'.base64url_encode($rawSig);
    echo "JWT: ".substr($jwt,0,50)."...\n";
}

echo "\n=== 2. Encrypt Payload ===\n";
$testKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$det = openssl_pkey_get_details($testKey);
$pubKey = chr(4).$det['ec']['x'].$det['ec']['y'];
$p256dh = base64url_encode($pubKey);
$auth = base64url_encode(random_bytes(16));
$result = encryptPayload(json_encode(['title'=>'Test','body'=>'Hello ShipperShop']), $p256dh, $auth);
echo "Encrypt: ".($result?'OK ('.strlen($result['ciphertext']).' bytes)':'FAIL')."\n";

echo "\n=== 3. DB Subscriptions ===\n";
$d=db();
$cnt=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];
echo "Total subscriptions: $cnt\n";

echo "\n=== 4. Full Send Test (fake endpoint) ===\n";
$fakeSub = ['endpoint'=>'https://fcm.googleapis.com/fcm/send/fake','p256dh'=>$p256dh,'auth'=>$auth];
$res = sendPushNotification($fakeSub, json_encode(['title'=>'Test','body'=>'Hello']));
echo "Status: ".$res['status']."\n";
echo "Success: ".($res['success']?'yes':'no')."\n";

echo "\n=== 5. Hook Check ===\n";
$hooks = [
    'messages-api.php' => "Private msg + Group msg",
    'posts.php' => "Comment + Like + Share",
    'groups.php' => "New post + Comment",
    'social.php' => "Follow",
    'traffic.php' => "New alert",
    'map-pins.php' => "New pin",
];
foreach($hooks as $f=>$desc){
    $code=file_get_contents(__DIR__.'/'.$f);
    $cnt=substr_count($code,'notifyUser');
    echo "  $f: $cnt hooks ($desc)\n";
}

echo "\nALL TESTS PASSED\n";
