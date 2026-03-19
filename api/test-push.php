<?php
error_reporting(E_ALL);ini_set('display_errors','1');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/vapid_keys.php';
header('Content-Type: text/plain');

echo "Testing PEM generation...\n";
$d = base64_decode(strtr(VAPID_PRIVATE_KEY,'-_','+/').str_repeat('=', (4-strlen(VAPID_PRIVATE_KEY)%4)%4));
echo "Private key bytes: ".strlen($d)."\n";

// Method 1: minimal DER
$der1 = "\x30\x41\x02\x01\x01\x04\x20" . $d . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
$pem1 = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der1), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
$key1 = openssl_pkey_get_private($pem1);
echo "Method 1 (minimal): ".($key1?'OK':'FAIL')."\n";
if(!$key1){echo "  err: ".openssl_error_string()."\n";}

// Method 2: full DER with public key
if(!$key1){
    // Try generating a key pair, then replacing private part
    $tmpKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    openssl_pkey_export($tmpKey, $tmpPem);
    echo "Template PEM generated\n";
    echo "Template PEM first 50: ".substr($tmpPem,0,80)."\n";
}

// Method 3: use JWK-to-PEM conversion
// Full SEC1 DER: 30 77 02 01 01 04 20 <d> a0 0a 06 08 2a 86 48 ce 3d 02 01 06 08 2a 86 48 ce 3d 03 01 07 a1 44 03 42 00 <pub>
echo "\nMethod 3 (full SEC1 with pub)...\n";
// Derive public key from private
$tmpKey2 = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
openssl_pkey_export($tmpKey2,$tmpPem2);
// Decode and replace
$tmpDer2 = base64_decode(str_replace(["-----BEGIN EC PRIVATE KEY-----","-----END EC PRIVATE KEY-----","\n","\r"],"",$tmpPem2));
echo "Template DER len: ".strlen($tmpDer2)."\n";
echo "Template DER hex: ".bin2hex(substr($tmpDer2,0,10))."\n";

echo "\nDONE\n";
