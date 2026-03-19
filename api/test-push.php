<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
header('Content-Type: text/plain');
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/vapid_keys.php';

echo "=== Debug Encryption ===\n\n";

// Test key pair
$localKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$det=openssl_pkey_get_details($localKey);
$localPub=chr(4).$det['ec']['x'].$det['ec']['y'];
echo "1. Local key: OK (".strlen($localPub)." bytes pub)\n";

// Simulated client key
$clientKey=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
$cDet=openssl_pkey_get_details($clientKey);
$clientPub=chr(4).$cDet['ec']['x'].$cDet['ec']['y'];
$clientPubB64=rtrim(strtr(base64_encode($clientPub),'+/','-_'),'=');
$clientAuth=rtrim(strtr(base64_encode(random_bytes(16)),'+/','-_'),'=');

echo "2. Client pub: ".strlen($clientPub)." bytes\n";
echo "3. Client pub b64: ".strlen($clientPubB64)." chars\n";

// Test base64url_decode
function base64url_decode2($data){return base64_decode(strtr($data,'-_','+/').str_repeat('=',(4-strlen($data)%4)%4));}
function base64url_encode2($data){return rtrim(strtr(base64_encode($data),'+/','-_'),'=');}

$decoded=base64url_decode2($clientPubB64);
echo "4. Decoded back: ".strlen($decoded)." bytes (should be 65)\n";
echo "   First byte: ".ord($decoded[0])." (should be 4)\n";

// Test ECDH
echo "\n5. ECDH test:\n";
$remotePubBin=base64url_decode2($clientPubB64);
$x=substr($remotePubBin,1,32);
$y=substr($remotePubBin,33,32);
echo "   x len: ".strlen($x).", y len: ".strlen($y)."\n";

// Create PEM from raw
$der="\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00".$remotePubBin;
$pem="-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der),64,"\n")."-----END PUBLIC KEY-----\n";
$remotePubKey=openssl_pkey_get_public($pem);
echo "   Remote pub PEM valid: ".($remotePubKey?'YES':'NO')."\n";
if(!$remotePubKey){
    while($e=openssl_error_string())echo "   openssl err: $e\n";
}

// openssl_pkey_derive
if($remotePubKey){
    $shared=openssl_pkey_derive($localKey,$remotePubKey,256);
    echo "   Shared secret: ".($shared?strlen($shared).' bytes':'FAIL')."\n";
    if(!$shared){while($e=openssl_error_string())echo "   err: $e\n";}
}

// Test HKDF
echo "\n6. HKDF test: ";
function hkdf2($salt,$ikm,$info,$length){$prk=hash_hmac('sha256',$ikm,$salt,true);$t='';$output='';for($i=1;strlen($output)<$length;$i++){$t=hash_hmac('sha256',$t.$info.chr($i),$prk,true);$output.=$t;}return substr($output,0,$length);}
$testHkdf=hkdf2(random_bytes(16),random_bytes(32),"test",16);
echo strlen($testHkdf)." bytes (should be 16)\n";

// Test AES-128-GCM
echo "\n7. AES-128-GCM test: ";
$aesKey=random_bytes(16);
$nonce=random_bytes(12);
$tag='';
$ct=openssl_encrypt("hello",'aes-128-gcm',$aesKey,OPENSSL_RAW_DATA,$nonce,$tag,'',16);
echo ($ct!==false?'OK ('.strlen($ct).' + tag '.strlen($tag).')':'FAIL')."\n";

// Full encryption test
echo "\n8. Full encryptPayload test:\n";
if($remotePubKey && $shared){
    $userAuth=base64url_decode2($clientAuth);
    echo "   userAuth: ".strlen($userAuth)." bytes\n";
    
    $info="WebPush: info\0".$remotePubBin.$localPub;
    $ikm=hkdf2($userAuth,$shared,$info,32);
    echo "   IKM: ".strlen($ikm)." bytes\n";
    
    $salt=random_bytes(16);
    $contentKey=hkdf2($salt,$ikm,"Content-Encoding: aes128gcm\0",16);
    $nonce2=hkdf2($salt,$ikm,"Content-Encoding: nonce\0",12);
    echo "   Content key: ".strlen($contentKey).", nonce: ".strlen($nonce2)."\n";
    
    $padded="\x00\x00"."test payload";
    $tag2='';
    $encrypted2=openssl_encrypt($padded,'aes-128-gcm',$contentKey,OPENSSL_RAW_DATA,$nonce2,$tag2,'',16);
    echo "   Encrypted: ".($encrypted2!==false?strlen($encrypted2).' bytes':'FAIL')."\n";
    echo "   Tag: ".strlen($tag2)." bytes\n";
}

echo "\nDONE\n";
