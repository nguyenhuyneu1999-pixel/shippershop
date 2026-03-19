<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
echo "START\n";

$vapidFile=__DIR__.'/../includes/vapid_keys.php';
if(file_exists($vapidFile)){
    $content=file_get_contents($vapidFile);
    echo "File content:\n$content\n";
    // Try to include it
    try{
        require $vapidFile;
        echo "PUBLIC KEY: ".VAPID_PUBLIC_KEY."\n";
    }catch(Throwable $e){
        echo "Error including: ".$e->getMessage()."\n";
    }
}else{
    echo "File not found, generating...\n";
    $key=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    if($key){
        $det=openssl_pkey_get_details($key);
        $pubKey=chr(4).$det['ec']['x'].$det['ec']['y'];
        $pubB64=rtrim(strtr(base64_encode($pubKey),'+/','-_'),'=');
        $privB64=rtrim(strtr(base64_encode($det['ec']['d']),'+/','-_'),'=');
        file_put_contents($vapidFile,"<?php\ndefine('VAPID_PUBLIC_KEY','$pubB64');\ndefine('VAPID_PRIVATE_KEY','$privB64');\ndefine('VAPID_SUBJECT','mailto:admin@shippershop.vn');\n");
        echo "Generated PUBLIC: $pubB64\n";
    }else{
        echo "Key gen failed\n";
    }
}
echo "DONE\n";
