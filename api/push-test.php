<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
echo "1\n"; flush();

// Pure openssl test - no includes
$key = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo "2 keygen:".($key?"OK":"FAIL")."\n"; flush();

openssl_pkey_export($key, $pem);
echo "3 pem:".strlen($pem)."\n"; flush();

$det = openssl_pkey_get_details($key);
$pubKey = chr(4).$det['ec']['x'].$det['ec']['y'];
$privD = $det['ec']['d'];
echo "4 pub:".strlen($pubKey)." priv:".strlen($privD)."\n"; flush();

// Save PEM to temp file, reload
$tmpFile = '/tmp/test_ec_key.pem';
file_put_contents($tmpFile, $pem);
$key2 = openssl_pkey_get_private('file://'.$tmpFile);
echo "5 reload:".($key2?"OK":"FAIL")."\n"; flush();

// Sign
$ok = openssl_sign("test", $sig, $key2, OPENSSL_ALGO_SHA256);
echo "6 sign:".($ok?"OK":"FAIL")."\n"; flush();

// Now test: create PEM from raw d bytes (our approach)
$derBody = "\x02\x01\x01\x04\x20" . $privD . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
$der = "\x30" . chr(strlen($derBody)) . $derBody;
$rawPem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der),64,"\n") . "-----END EC PRIVATE KEY-----\n";
$key3 = openssl_pkey_get_private($rawPem);
echo "7 rawPem:".($key3?"OK":"FAIL - ".openssl_error_string())."\n"; flush();

// Test ECDH derive
$keyB = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$shared = openssl_pkey_derive($key, $keyB, 256);
echo "8 derive:".($shared?"OK ".strlen($shared):"FAIL")."\n"; flush();

echo "DONE\n";
