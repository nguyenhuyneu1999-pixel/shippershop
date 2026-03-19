<?php
require_once __DIR__.'/../includes/db.php';
$vapidFile=__DIR__.'/../includes/vapid_keys.php';
if(file_exists($vapidFile)){
    require $vapidFile;
    echo "PUBLIC: ".VAPID_PUBLIC_KEY."\n";
    echo "PRIVATE: ".substr(VAPID_PRIVATE_KEY,0,10)."...\n";
    echo "SUBJECT: ".VAPID_SUBJECT."\n";
}else{
    echo "NO VAPID FILE\n";
}
echo "\n=== Test file_get_contents POST ===\n";
echo "allow_url_fopen: ".ini_get('allow_url_fopen')."\n";
echo "stream_wrapper: ".(in_array('https',stream_get_wrappers())?'YES':'NO')."\n";
