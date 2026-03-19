<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
// Fix VAPID file with correct constant names
$vapidFile=__DIR__.'/../includes/vapid_keys.php';
$old=file_get_contents($vapidFile);
$new=str_replace("VAPID_PUBLIC","VAPID_PUBLIC_KEY",$old);
$new=str_replace("VAPID_PRIVATE","VAPID_PRIVATE_KEY",$new);
$new=str_replace("VAPID_PUBLIC_KEY_KEY","VAPID_PUBLIC_KEY",$new);
$new=str_replace("VAPID_PRIVATE_KEY_KEY","VAPID_PRIVATE_KEY",$new);
file_put_contents($vapidFile,$new);
echo "Fixed. Content:\n";
echo file_get_contents($vapidFile);
require $vapidFile;
echo "\nPUBLIC: ".VAPID_PUBLIC_KEY."\nDONE\n";
