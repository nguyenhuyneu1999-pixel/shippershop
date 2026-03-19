<?php
error_reporting(E_ALL);ini_set('display_errors','1');
header('Content-Type: text/plain');
require '/home/nhshiw2j/public_html/includes/vapid_keys.php';

// Test ECDH with BOTH arg orders
$privKey = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo "privKey: ".($privKey?"OK":"FAIL")."\n";

$localKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$localDet = openssl_pkey_get_details($localKey);
$localPub = chr(4).$localDet['ec']['x'].$localDet['ec']['y'];

$pubDer = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $localPub;
$pubPem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($pubDer),64,"\n")."-----END PUBLIC KEY-----\n";
$pubKey = openssl_pkey_get_public($pubPem);
echo "pubKey: ".($pubKey?"OK":"FAIL")."\n";

// Order 1: derive(pub, priv) - PHP 8.2 docs say this
while(openssl_error_string());
$s1 = @openssl_pkey_derive($pubKey, $privKey, 32);
$e1 = openssl_error_string();
echo "derive(pub,priv): ".($s1?"OK ".strlen($s1):"FAIL $e1")."\n";

// Order 2: derive(priv, pub)
while(openssl_error_string());
$s2 = @openssl_pkey_derive($privKey, $pubKey, 32);
$e2 = openssl_error_string();
echo "derive(priv,pub): ".($s2?"OK ".strlen($s2):"FAIL $e2")."\n";

// Test with 2 fresh EC keys (known working)
$kA = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$kB = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
// Get public PEM of B
$detB = openssl_pkey_get_details($kB);
$pubBBin = chr(4).$detB['ec']['x'].$detB['ec']['y'];
$pubBDer = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $pubBBin;
$pubBPem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($pubBDer),64,"\n")."-----END PUBLIC KEY-----\n";
$pubBKey = openssl_pkey_get_public($pubBPem);

while(openssl_error_string());
$s3 = @openssl_pkey_derive($pubBKey, $kA, 32);
$e3 = openssl_error_string();
echo "fresh derive(pubB, privA): ".($s3?"OK ".strlen($s3):"FAIL $e3")."\n";

echo "DONE\n";
