<?php
error_reporting(E_ALL);ini_set('display_errors','1');
header('Content-Type: text/plain');

// Fix vapid_keys.php constant names
$f='/home/nhshiw2j/public_html/includes/vapid_keys.php';
$c=file_get_contents($f);
echo "BEFORE:\n$c\n";

// Ensure consistent naming
if(strpos($c,'VAPID_PUBLIC_KEY')===false && strpos($c,'VAPID_PUBLIC')!==false){
    $c=str_replace("VAPID_PUBLIC","VAPID_PUBLIC_KEY",$c);
    $c=str_replace("VAPID_PRIVATE","VAPID_PRIVATE_KEY",$c);
    // Fix double _KEY_KEY
    $c=str_replace("_KEY_KEY","_KEY",$c);
    file_put_contents($f,$c);
    echo "FIXED!\n";
}
echo "\nAFTER:\n".file_get_contents($f)."\n";

// Verify
require $f;
echo "PUBLIC: ".VAPID_PUBLIC_KEY."\n";
echo "PRIVATE len: ".strlen(VAPID_PRIVATE_KEY)."\n";
echo "SUBJECT: ".VAPID_SUBJECT."\n";
