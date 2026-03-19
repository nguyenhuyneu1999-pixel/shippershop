<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/vapid_keys.php';

echo "=== PUSH PIPELINE TEST ===\n";
echo "PHP: ".phpversion()."\n";
echo "openssl: ".(extension_loaded('openssl')?'YES':'NO')."\n";
echo "VAPID_PUBLIC_KEY: ".substr(VAPID_PUBLIC_KEY,0,20)."...\n";

// Test 1: EC key generation
echo "\n--- Test 1: EC key gen ---\n";
$key=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo $key?"OK: key created\n":"FAIL: ".openssl_error_string()."\n";

// Test 2: openssl_pkey_derive exists
echo "\n--- Test 2: openssl_pkey_derive ---\n";
echo function_exists('openssl_pkey_derive')?"OK: function exists\n":"FAIL: function missing\n";

// Test 3: AES-128-GCM
echo "\n--- Test 3: AES-128-GCM ---\n";
$ciphers=openssl_get_cipher_methods();
echo in_array('aes-128-gcm',$ciphers)?"OK: aes-128-gcm supported\n":"FAIL: not supported\n";

// Test 4: VAPID JWT signing
echo "\n--- Test 4: VAPID JWT sign ---\n";
require_once __DIR__.'/../includes/push-helper.php';
$privPem=createPemFromPrivateKey(VAPID_PRIVATE_KEY);
$privKey=openssl_pkey_get_private($privPem);
echo $privKey?"OK: private key loaded\n":"FAIL: ".openssl_error_string()."\n";
if($privKey){
    $data='test.payload';
    $sig='';
    $ok=openssl_sign($data,$sig,$privKey,OPENSSL_ALGO_SHA256);
    echo $ok?"OK: signed (".(strlen($sig))." bytes)\n":"FAIL: sign error\n";
}

// Test 5: ECDH key exchange
echo "\n--- Test 5: ECDH ---\n";
if($key){
    $det=openssl_pkey_get_details($key);
    $localPub=chr(4).$det['ec']['x'].$det['ec']['y'];
    // Create another key to test derive
    $key2=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    if($key2){
        $shared=openssl_pkey_derive($key,$key2,256);
        echo $shared?"OK: ECDH shared secret (".strlen($shared)." bytes)\n":"FAIL: derive error\n";
    }
}

// Test 6: file_get_contents for HTTPS
echo "\n--- Test 6: HTTPS POST ---\n";
echo "allow_url_fopen: ".ini_get('allow_url_fopen')."\n";
$ctx=stream_context_create(['http'=>['method'=>'POST','header'=>"Content-Length: 0\r\nTTL: 0",'timeout'=>5],'ssl'=>['verify_peer'=>false]]);
echo "stream context OK\n";

// Test 7: Check subscriptions
echo "\n--- Test 7: Subscriptions ---\n";
$d=db();
$subs=$d->fetchAll("SELECT id,user_id,LEFT(endpoint,60) as ep FROM push_subscriptions ORDER BY id DESC LIMIT 5");
echo "Total subs: ".$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions")['c']."\n";
foreach($subs as $s){echo "  id=".$s['id']." user=".$s['user_id']." ep=".$s['ep']."...\n";}

echo "\n=== ALL TESTS DONE ===\n";
