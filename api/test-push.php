<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain; charset=utf-8');

echo "=== ECDH Debug ===\n\n";

// Create two EC key pairs
$keyA=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$keyB=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$detA=openssl_pkey_get_details($keyA);
$detB=openssl_pkey_get_details($keyB);

echo "1. Keys generated: YES\n";

// Method 1: Direct openssl_pkey_derive with key objects
echo "\n2. Direct derive (key objects):\n";
$shared1=openssl_pkey_derive($keyA, $keyB, 32);
echo "   A→B: ".($shared1?strlen($shared1)." bytes":'FAIL')."\n";
if(!$shared1){while($e=openssl_error_string())echo "   err: $e\n";}

$shared2=openssl_pkey_derive($keyB, $keyA, 32);
echo "   B→A: ".($shared2?strlen($shared2)." bytes":'FAIL')."\n";
if(!$shared2){while($e=openssl_error_string())echo "   err: $e\n";}

// Method 2: Using PEM export/import for public key
echo "\n3. PEM-based derive:\n";
openssl_pkey_export($keyA,$pemA);
$detB_pub=$detB['key']; // This is the PEM public key
$pubKeyB=openssl_pkey_get_public($detB_pub);
echo "   B pub PEM valid: ".($pubKeyB?'YES':'NO')."\n";
if($pubKeyB){
    $shared3=openssl_pkey_derive($pubKeyB, $keyA, 32);
    echo "   PEM derive: ".($shared3?strlen($shared3)." bytes":'FAIL')."\n";
    if(!$shared3){while($e=openssl_error_string())echo "   err: $e\n";}
}

// Method 3: Raw binary → DER → PEM (what we do in computeECDH)
echo "\n4. Binary→DER→PEM derive:\n";
$rawPubB=chr(4).$detB['ec']['x'].$detB['ec']['y'];
echo "   Raw pub B: ".strlen($rawPubB)." bytes\n";
$derB="\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00".$rawPubB;
$pemB="-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($derB),64,"\n")."-----END PUBLIC KEY-----\n";
echo "   DER length: ".strlen($derB)."\n";
$pubFromDer=openssl_pkey_get_public($pemB);
echo "   PEM from DER valid: ".($pubFromDer?'YES':'NO')."\n";
if(!$pubFromDer){while($e=openssl_error_string())echo "   err: $e\n";}

if($pubFromDer){
    // Try both parameter orders
    echo "\n5. Derive with DER-built PEM:\n";
    $s4=openssl_pkey_derive($pubFromDer, $keyA, 32);
    echo "   derive(pub,priv,32): ".($s4?strlen($s4)." bytes":'FAIL')."\n";
    if(!$s4){while($e=openssl_error_string())echo "   err: $e\n";}
    
    $s5=openssl_pkey_derive($pubFromDer, $keyA, 0);
    echo "   derive(pub,priv,0): ".($s5?strlen($s5)." bytes":'FAIL')."\n";
    if(!$s5){while($e=openssl_error_string())echo "   err: $e\n";}
    
    $s6=openssl_pkey_derive($pubFromDer, $keyA);
    echo "   derive(pub,priv): ".($s6?strlen($s6)." bytes":'FAIL')."\n";
    if(!$s6){while($e=openssl_error_string())echo "   err: $e\n";}
}

echo "\nDONE\n";
