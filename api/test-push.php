<?php
set_time_limit(10);
error_reporting(E_ALL);ini_set('display_errors','1');
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');

echo "=== Regenerate VAPID with full PEM ===\n";

$key=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
if(!$key){echo "FAILED\n";exit;}

openssl_pkey_export($key,$privPem);
$det=openssl_pkey_get_details($key);
$pubKey=chr(4).$det['ec']['x'].$det['ec']['y'];
$pubB64=rtrim(strtr(base64_encode($pubKey),'+/','-_'),'=');

// Store FULL PEM instead of just raw bytes
$vapidFile=__DIR__.'/../includes/vapid_keys.php';
$privPemEscaped=addslashes($privPem);
$content="<?php\ndefine('VAPID_PUBLIC_KEY','$pubB64');\ndefine('VAPID_PRIVATE_PEM','$privPemEscaped');\ndefine('VAPID_SUBJECT','mailto:admin@shippershop.vn');\n";
file_put_contents($vapidFile,$content);
echo "PUBLIC: $pubB64\n";
echo "PEM stored (full)\n";

// Verify it works
require $vapidFile;
$loaded=openssl_pkey_get_private(VAPID_PRIVATE_PEM);
echo "Load PEM: ".($loaded?'OK':'FAIL')."\n";

// Test sign
if($loaded){
    $payload="test.test";
    openssl_sign($payload,$sig,$loaded,OPENSSL_ALGO_SHA256);
    echo "Sign: ".($sig?'OK ('.strlen($sig).' bytes)':'FAIL')."\n";
}

echo "DONE\n";
