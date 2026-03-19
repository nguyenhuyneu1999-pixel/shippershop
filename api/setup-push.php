<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "PHP: ".phpversion()."\n";
echo "openssl: ".(extension_loaded('openssl')?'YES':'NO')."\n";
echo "allow_url_fopen: ".ini_get('allow_url_fopen')."\n";

// Create table
try{
$d->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(500) NOT NULL,
    auth VARCHAR(200) NOT NULL,
    category VARCHAR(50) DEFAULT 'all',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Table: OK\n";
}catch(Exception $e){echo "Table err: ".$e->getMessage()."\n";}

// Generate VAPID keys
$vapidFile=__DIR__.'/../includes/vapid_keys.php';
if(!file_exists($vapidFile)){
    $config=['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC];
    $key=openssl_pkey_new($config);
    if(!$key){echo "VAPID FAILED: ".openssl_error_string()."\n";exit;}
    openssl_pkey_export($key,$privPem);
    $det=openssl_pkey_get_details($key);
    $pubKey=chr(4).$det['ec']['x'].$det['ec']['y'];
    $pubB64=rtrim(strtr(base64_encode($pubKey),'+/','-_'),'=');
    $privB64=rtrim(strtr(base64_encode($det['ec']['d']),'+/','-_'),'=');
    $content="<?php\ndefine('VAPID_PUBLIC_KEY','$pubB64');\ndefine('VAPID_PRIVATE_KEY','$privB64');\ndefine('VAPID_SUBJECT','mailto:admin@shippershop.vn');\n";
    file_put_contents($vapidFile,$content);
    echo "VAPID PUBLIC: $pubB64\n";
}else{
    require $vapidFile;
    echo "VAPID PUBLIC: ".VAPID_PUBLIC_KEY."\n";
}
echo "DONE\n";
