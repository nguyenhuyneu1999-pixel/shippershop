<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');

echo "START\n";

require_once __DIR__.'/../includes/db.php';
echo "db OK\n";

require_once __DIR__.'/../includes/vapid_keys.php';
echo "vapid OK: ".substr(VAPID_PUBLIC_KEY,0,10)."\n";
echo "priv len: ".strlen(VAPID_PRIVATE_KEY)."\n";

// Simplest possible approach: generate key from scratch, store PEM
$key = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
echo "keygen: ".($key?"OK":"FAIL")."\n";

if ($key) {
    openssl_pkey_export($key, $pem);
    echo "export OK, pem len: ".strlen($pem)."\n";
    
    // Reload from PEM
    $key2 = openssl_pkey_get_private($pem);
    echo "reload: ".($key2?"OK":"FAIL")."\n";
    
    // Sign test
    $ok = openssl_sign("test", $sig, $key2, OPENSSL_ALGO_SHA256);
    echo "sign: ".($ok?"OK (".strlen($sig)." bytes)":"FAIL")."\n";
    
    // Derive test
    $key3 = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    $shared = openssl_pkey_derive($key2, $key3, 256);
    echo "derive: ".($shared?"OK (".strlen($shared)." bytes)":"FAIL")."\n";
}

echo "DONE\n";
echo ob_get_clean();
