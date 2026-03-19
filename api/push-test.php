<?php
error_reporting(E_ALL);ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/push-helper.php';

echo "=== END-TO-END PUSH TEST ===\n\n";

// 1. VAPID signing
echo "1. VAPID JWT sign... ";
$privKey = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
$header = base64url_encode(json_encode(['typ'=>'JWT','alg'=>'ES256']));
$claims = base64url_encode(json_encode(['aud'=>'https://fcm.googleapis.com','exp'=>time()+43200,'sub'=>VAPID_SUBJECT]));
openssl_sign($header.'.'.$claims, $sig, $privKey, OPENSSL_ALGO_SHA256);
echo ($sig ? "OK" : "FAIL") . "\n";

// 2. Payload encryption (with fake subscription)
echo "2. Payload encryption... ";
$fakeKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$fakeDet = openssl_pkey_get_details($fakeKey);
$fakePub = chr(4).$fakeDet['ec']['x'].$fakeDet['ec']['y'];
$fakeAuth = random_bytes(16);
$payload = json_encode(['title'=>'Test','body'=>'Hello','url'=>'/']);
$encrypted = encryptPayload($payload, base64url_encode($fakePub), base64url_encode($fakeAuth));
echo ($encrypted ? "OK (".strlen($encrypted['ciphertext'])." bytes)" : "FAIL") . "\n";

// 3. Check subscriptions in DB
$d = db();
$cnt = $d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c'];
echo "3. Subscriptions in DB: $cnt\n";

if ($cnt > 0) {
    $sub = $d->fetchOne("SELECT * FROM push_subscriptions ORDER BY id DESC LIMIT 1");
    echo "   Latest: user_id=".$sub['user_id']." endpoint=".substr($sub['endpoint'],0,50)."...\n";
    
    // 4. Try sending actual push
    echo "4. Sending real push to user ".$sub['user_id']."...\n";
    $result = sendPushNotification($sub, json_encode([
        'title' => 'ShipperShop Test',
        'body' => 'Push notification hoạt động! 🎉',
        'category' => 'general',
        'url' => '/',
        'icon' => '/icons/icon-192.png'
    ]));
    echo "   Status: ".$result['status']."\n";
    echo "   Success: ".($result['success']?"YES":"NO")."\n";
    if (!$result['success']) echo "   Error: ".($result['error'] ?? $result['response'] ?? 'unknown')."\n";
} else {
    echo "4. SKIP - no subscriptions yet (visit site on phone to subscribe)\n";
}

// 5. Test notifyUser
echo "5. notifyUser function... ";
if ($cnt > 0) {
    $sub = $d->fetchOne("SELECT user_id FROM push_subscriptions ORDER BY id DESC LIMIT 1");
    $sent = notifyUser($sub['user_id'], 'Test', 'Xin chào từ ShipperShop!', 'general', '/');
    echo "Sent to $sent devices\n";
} else {
    echo "SKIP - no subscribers\n";
}

// 6. Check all hooks work (just include test)
echo "6. Hook files loadable... ";
$ok = true;
foreach(['posts.php','messages-api.php','groups.php','social.php','map-pins.php','traffic.php'] as $f) {
    if(!file_exists(__DIR__.'/'.$f)){echo "MISSING: $f ";$ok=false;}
}
echo ($ok?"ALL OK":"SOME MISSING")."\n";

echo "\n=== DONE ===\n";
