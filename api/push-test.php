<?php
error_reporting(E_ALL);ini_set('display_errors','1');
header('Content-Type: text/plain');

echo "=== FULL PUSH PIPELINE TEST ===\n";

// 1. Key gen
$keyA = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$keyB = openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$detB = openssl_pkey_get_details($keyB);
$remotePubBin = chr(4).$detB['ec']['x'].$detB['ec']['y'];
echo "1. Keys: OK\n";

// 2. Build PEM from raw pub key bytes (this is what computeECDH does)
$der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $remotePubBin;
$pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
$remotePubKey = openssl_pkey_get_public($pem);
echo "2. Remote pub key from bytes: ".($remotePubKey?"OK":"FAIL")."\n";

// 3. ECDH derive
$shared = openssl_pkey_derive($keyA, $remotePubKey, 256);
echo "3. ECDH derive: ".($shared?"OK (".strlen($shared)." bytes)":"FAIL")."\n";

// 4. Test createPemFromPrivateKey
$detA = openssl_pkey_get_details($keyA);
$privD = $detA['ec']['d'];
$derBody = "\x02\x01\x01\x04\x20" . $privD . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
$derFull = "\x30" . chr(strlen($derBody)) . $derBody;
$privPem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($derFull),64,"\n") . "-----END EC PRIVATE KEY-----\n";
$reloaded = openssl_pkey_get_private($privPem);
echo "4. PEM from raw d: ".($reloaded?"OK":"FAIL")."\n";

// 5. VAPID JWT sign with reloaded key
$ok = openssl_sign("test.payload", $sig, $reloaded, OPENSSL_ALGO_SHA256);
echo "5. JWT sign: ".($ok?"OK":"FAIL")."\n";

// 6. Full encryption test
$salt = random_bytes(16);
$tag='';
$encrypted = openssl_encrypt("test payload","aes-128-gcm",random_bytes(16),OPENSSL_RAW_DATA,random_bytes(12),$tag,'',16);
echo "6. AES-128-GCM: ".($encrypted!==false?"OK":"FAIL")."\n";

// 7. Test with actual VAPID keys
require_once __DIR__.'/../includes/vapid_keys.php';
$vpd = base64_decode(strtr(VAPID_PRIVATE_KEY,'-_','+/').str_repeat('=',(4-strlen(VAPID_PRIVATE_KEY)%4)%4));
echo "7. VAPID_PRIVATE decoded: ".strlen($vpd)." bytes\n";
$vDerBody = "\x02\x01\x01\x04\x20" . $vpd . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
$vDer = "\x30" . chr(strlen($vDerBody)) . $vDerBody;
$vPem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($vDer),64,"\n") . "-----END EC PRIVATE KEY-----\n";
$vKey = openssl_pkey_get_private($vPem);
echo "8. VAPID key reload: ".($vKey?"OK":"FAIL")."\n";

// 8. VAPID JWT full test
$header = rtrim(strtr(base64_encode('{"typ":"JWT","alg":"ES256"}'),'+/','-_'),'=');
$claims = rtrim(strtr(base64_encode(json_encode(['aud'=>'https://fcm.googleapis.com','exp'=>time()+43200,'sub'=>VAPID_SUBJECT])),'+/','-_'),'=');
$input = $header.'.'.$claims;
openssl_sign($input, $derSig, $vKey, OPENSSL_ALGO_SHA256);
echo "9. VAPID JWT: ".($derSig?"OK (".strlen($derSig)." bytes)":"FAIL")."\n";

// 9. Check subscriptions
require_once __DIR__.'/../includes/db.php';
$d=db();
$cnt=$d->fetchOne("SELECT COUNT(*) as c FROM push_subscriptions");
echo "10. Subscriptions: ".$cnt['c']."\n";

echo "\n=== ALL OK ===\n";
