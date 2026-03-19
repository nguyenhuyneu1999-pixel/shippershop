<?php
error_reporting(E_ALL);ini_set('display_errors','1');
header('Content-Type: text/plain');
$f='/home/nhshiw2j/public_html/includes/vapid_keys.php';
require $f;
echo "PUBLIC_KEY: ".VAPID_PUBLIC_KEY."\n";
echo "PRIVATE_PEM type: ".gettype(VAPID_PRIVATE_PEM)."\n";
echo "PRIVATE_PEM starts: ".substr(VAPID_PRIVATE_PEM,0,40)."\n";
echo "SUBJECT: ".VAPID_SUBJECT."\n";

// Test sign with PEM directly
$privKey = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo "\nKey loaded: ".($privKey?"YES":"NO")."\n";
if($privKey){
    openssl_sign("test.data", $sig, $privKey, OPENSSL_ALGO_SHA256);
    echo "Sign: OK (".strlen($sig)." bytes)\n";
    
    // Test ECDH with PEM key
    $localKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    $localDet = openssl_pkey_get_details($localKey);
    $localPub = chr(4).$localDet['ec']['x'].$localDet['ec']['y'];
    
    // Derive using PEM private + fresh public
    $det = openssl_pkey_get_details($localKey);
    $pubDer = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $localPub;
    $pubPem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($pubDer),64,"\n")."-----END PUBLIC KEY-----\n";
    $pubKey = openssl_pkey_get_public($pubPem);
    echo "Pub from bytes: ".($pubKey?"OK":"FAIL")."\n";
    
    // derive(privKey, pubKey)
    $shared = openssl_pkey_derive($privKey, $pubKey, 32);
    echo "ECDH derive: ".($shared?"OK (".strlen($shared)." bytes)":"FAIL - ".openssl_error_string())."\n";
}
echo "DONE\n";
