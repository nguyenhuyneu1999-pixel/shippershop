<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
echo "openssl: ".(extension_loaded('openssl')?'YES':'NO')."\n";
echo "gmp: ".(extension_loaded('gmp')?'YES':'NO')."\n";
echo "mbstring: ".(extension_loaded('mbstring')?'YES':'NO')."\n";
echo "curl: ".(extension_loaded('curl')?'YES':'NO')."\n";
echo "PHP: ".phpversion()."\n";
// Create push_subscriptions table
$d=db();
try{
$d->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(500) NOT NULL,
    auth VARCHAR(500) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "push_subscriptions table: OK\n";
}catch(Exception $e){echo "table: ".$e->getMessage()."\n";}

// Generate VAPID keys if not exist
$vapidFile='/home/nhshiw2j/public_html/includes/vapid_keys.php';
if(!file_exists($vapidFile)){
    $key=openssl_pkey_new(['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC]);
    if(!$key){echo "VAPID key gen FAILED\n";exit;}
    openssl_pkey_export($key,$privPem);
    $details=openssl_pkey_get_details($key);
    $pubX=$details['ec']['x'];
    $pubY=$details['ec']['y'];
    $pubKey=chr(4).$pubX.$pubY;
    $pubB64=rtrim(strtr(base64_encode($pubKey),'+/','-_'),'=');
    $privD=$details['ec']['d'];
    $privB64=rtrim(strtr(base64_encode($privD),'+/','-_'),'=');
    file_put_contents($vapidFile,"<?php\ndefine('VAPID_PUBLIC','$pubB64');\ndefine('VAPID_PRIVATE','$privB64');\ndefine('VAPID_SUBJECT','mailto:admin@shippershop.vn');\n");
    echo "VAPID keys generated: $pubB64\n";
}else{
    include $vapidFile;
    echo "VAPID public key: ".VAPID_PUBLIC."\n";
}
