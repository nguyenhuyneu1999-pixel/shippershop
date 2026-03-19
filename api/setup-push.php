<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
echo "PHP: ".phpversion()."\n";

// Table
try{
$d->query("CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(500) NOT NULL,
    auth VARCHAR(200) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Table: OK\n";
}catch(Exception $e){echo "Table: ".$e->getMessage()."\n";}

// VAPID
$vapidFile=__DIR__.'/../includes/vapid_keys.php';
echo "vapidFile: $vapidFile\n";
echo "exists: ".(file_exists($vapidFile)?'YES':'NO')."\n";
echo "writable dir: ".(is_writable(dirname($vapidFile))?'YES':'NO')."\n";

if(!file_exists($vapidFile)){
    echo "Generating VAPID...\n";
    $config=['curve_name'=>'prime256v1','private_key_type'=>OPENSSL_KEYTYPE_EC];
    $key=openssl_pkey_new($config);
    if(!$key){
        echo "openssl_pkey_new FAILED\n";
        while($e=openssl_error_string())echo "  openssl err: $e\n";
        // Fallback: use hardcoded test keys
        $pubB64='BEl62iUYgUivxIkv69yViikEytlHI0TYKYVXaDndFULSM3kPzHr-_FPhMhIOWWbU4Zx7syL6PZGAIXNPK5pWTyg';
        $privB64='test_key_placeholder';
        echo "Using fallback keys\n";
    }else{
        openssl_pkey_export($key,$privPem);
        $det=openssl_pkey_get_details($key);
        echo "key type: ".$det['type']."\n";
        echo "ec x len: ".strlen($det['ec']['x'])."\n";
        echo "ec y len: ".strlen($det['ec']['y'])."\n";
        echo "ec d len: ".strlen($det['ec']['d'])."\n";
        $pubKey=chr(4).$det['ec']['x'].$det['ec']['y'];
        $pubB64=rtrim(strtr(base64_encode($pubKey),'+/','-_'),'=');
        $privB64=rtrim(strtr(base64_encode($det['ec']['d']),'+/','-_'),'=');
    }
    $content="<?php\ndefine('VAPID_PUBLIC_KEY','".$pubB64."');\ndefine('VAPID_PRIVATE_KEY','".$privB64."');\ndefine('VAPID_SUBJECT','mailto:admin@shippershop.vn');\n";
    $written=file_put_contents($vapidFile,$content);
    echo "written: ".($written?$written.' bytes':'FAILED')."\n";
    echo "VAPID PUBLIC: $pubB64\n";
}else{
    require $vapidFile;
    echo "VAPID PUBLIC: ".VAPID_PUBLIC_KEY."\n";
}
echo "DONE\n";
