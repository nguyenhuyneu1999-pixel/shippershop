<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/vapid_keys.php';

echo "VAPID_PRIVATE_KEY length: ".strlen(VAPID_PRIVATE_KEY)."\n";

// Debug: try to decode the private key
$d = base64_decode(strtr(VAPID_PRIVATE_KEY,'-_','+/').str_repeat('=', (4 - strlen(VAPID_PRIVATE_KEY) % 4) % 4));
echo "Decoded private key length: ".strlen($d)." bytes\n";

// The correct DER format for an EC private key (P-256)
// Full ASN.1 with OID for prime256v1
$der = "\x30" . chr(0x41 + strlen($d) - 32)
    . "\x02\x01\x01"  // version
    . "\x04" . chr(strlen($d)) . $d  // private key
    . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // OID prime256v1

$pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der),64,"\n") . "-----END EC PRIVATE KEY-----\n";
echo "PEM:\n$pem\n";

$key = openssl_pkey_get_private($pem);
echo "Key loaded: " . ($key ? "YES" : "NO - ".openssl_error_string()) . "\n";

// Alternative: Use PEM directly with raw params
if (!$key) {
    echo "\n=== Try alternative DER format ===\n";
    // Standard format: SEQUENCE { INTEGER 1, OCTET STRING d, [0] OID }
    $privKeyDer = "\x30\x77" 
        . "\x02\x01\x01"  // version = 1
        . "\x04\x20" . $d  // OCTET STRING (32 bytes)
        . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"; // [0] OID
    
    $pem2 = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($privKeyDer),64,"\n") . "-----END EC PRIVATE KEY-----\n";
    echo "PEM2:\n$pem2\n";
    $key2 = openssl_pkey_get_private($pem2);
    echo "Key2 loaded: " . ($key2 ? "YES" : "NO - ".openssl_error_string()) . "\n";
}

// Alternative: generate fresh key pair and test
echo "\n=== Test with fresh key ===\n";
$freshKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
openssl_pkey_export($freshKey, $freshPem);
echo "Fresh PEM works: YES\n";
$freshDet = openssl_pkey_get_details($freshKey);
$freshD = $freshDet['ec']['d'];
echo "Fresh d length: ".strlen($freshD)."\n";

// Try the simple format that PHP itself produces
$okKey = openssl_pkey_get_private($freshPem);
echo "Fresh reload: ".($okKey?"YES":"NO")."\n";
echo "\nDONE\n";
