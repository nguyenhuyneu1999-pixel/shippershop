<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/vapid_keys.php';

echo "=== ECDH Deep Debug ===\n\n";

// Clear openssl errors
while(openssl_error_string()){}

// Create keys exactly like encryptPayload does
$localKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$localDet = openssl_pkey_get_details($localKey);
$localPub = chr(4).$localDet['ec']['x'].$localDet['ec']['y'];
echo "localKey OK, localPub: ".strlen($localPub)." bytes\n";

// Create client key (simulates browser)
$clientKey = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$cDet = openssl_pkey_get_details($clientKey);
$clientPubRaw = chr(4).$cDet['ec']['x'].$cDet['ec']['y'];
echo "clientPubRaw: ".strlen($clientPubRaw)." bytes\n";

// Simulate what encryptPayload does: base64url encode then decode
function b64ue($d){return rtrim(strtr(base64_encode($d),'+/','-_'),'=');}
function b64ud($d){return base64_decode(strtr($d,'-_','+/').str_repeat('=',(4-strlen($d)%4)%4));}

$clientPubB64 = b64ue($clientPubRaw);
echo "clientPubB64: ".strlen($clientPubB64)." chars\n";

$userPub = b64ud($clientPubB64);
echo "userPub (decoded): ".strlen($userPub)." bytes\n";
echo "First byte: 0x".bin2hex($userPub[0])." (should be 0x04)\n";
echo "Match raw: ".($userPub===$clientPubRaw?'YES':'NO')."\n";

// Now trace computeECDH exactly
echo "\n--- Inside computeECDH ---\n";
$remotePubKeyBin = $userPub;
echo "remotePubKeyBin: ".strlen($remotePubKeyBin)." bytes\n";

// Check lengths of x,y
$x = substr($remotePubKeyBin, 1, 32);
$y = substr($remotePubKeyBin, 33, 32);
echo "x: ".strlen($x)." bytes, y: ".strlen($y)." bytes\n";

// Build DER
$der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $remotePubKeyBin;
echo "DER: ".strlen($der)." bytes (should be 91)\n";
echo "DER hex start: ".bin2hex(substr($der,0,10))."\n";

// Build PEM
$b64 = base64_encode($der);
echo "Base64: ".strlen($b64)." chars\n";
$pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($b64, 64, "\n") . "-----END PUBLIC KEY-----\n";
echo "PEM:\n$pem\n";

// Clear errors before parse
while(openssl_error_string()){}

$remotePubKey = openssl_pkey_get_public($pem);
echo "remotePubKey: ".($remotePubKey?'VALID':'INVALID')."\n";
if(!$remotePubKey){
    while($e=openssl_error_string()) echo "  err: $e\n";
}

// Try derive
if($remotePubKey){
    while(openssl_error_string()){}
    $shared = openssl_pkey_derive($remotePubKey, $localKey, 32);
    echo "Shared secret: ".($shared?strlen($shared)." bytes":'FAIL')."\n";
    if(!$shared){while($e=openssl_error_string()) echo "  err: $e\n";}
}

// Also try the PHP 8.2+ approach: export pub PEM from key details
echo "\n--- Alternative: use key details PEM ---\n";
$clientPubPem = $cDet['key']; // OpenSSL's own PEM public key
echo "Client PEM from details:\n$clientPubPem\n";
$pubKey2 = openssl_pkey_get_public($clientPubPem);
echo "pubKey2: ".($pubKey2?'VALID':'INVALID')."\n";
if($pubKey2){
    $shared2 = openssl_pkey_derive($pubKey2, $localKey, 32);
    echo "Shared2: ".($shared2?strlen($shared2)." bytes":'FAIL')."\n";
}

echo "\nDONE\n";
