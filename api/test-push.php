<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/vapid_keys.php';

echo "=== Fix VAPID: regenerate with full PEM ===\n";

// Generate new VAPID key pair and store full PEM
$key = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
if(!$key){echo "FAILED\n";exit;}
openssl_pkey_export($key, $privPem);
$det = openssl_pkey_get_details($key);
$pubKey = chr(4) . $det['ec']['x'] . $det['ec']['y'];
$pubB64 = rtrim(strtr(base64_encode($pubKey),'+/','-_'),'=');

// Verify PEM works
$testKey = openssl_pkey_get_private($privPem);
echo "PEM valid: ".($testKey?'YES':'NO')."\n";

// Test sign
$signed = openssl_sign('test', $sig, $testKey, OPENSSL_ALGO_SHA256);
echo "Sign works: ".($signed?'YES':'NO')."\n";

// Save with PEM format
$vapidFile = __DIR__.'/../includes/vapid_keys.php';
$privPemEscaped = str_replace("'", "\\'", $privPem);
$content = "<?php\ndefine('VAPID_PUBLIC_KEY','$pubB64');\ndefine('VAPID_PRIVATE_PEM','$privPemEscaped');\ndefine('VAPID_SUBJECT','mailto:admin@shippershop.vn');\n";
file_put_contents($vapidFile, $content);
echo "Saved new keys\n";
echo "PUBLIC: $pubB64\n";
echo "PEM lines: ".substr_count($privPem,"\n")."\n";
echo "DONE\n";
