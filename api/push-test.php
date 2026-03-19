<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$cols=$d->fetchAll("SHOW COLUMNS FROM marketplace_listings");
foreach($cols as $c) echo $c['Field']." | ".$c['Type']."\n";
// Add showcase_images column for full-page inline images
try{
    $d->query("ALTER TABLE marketplace_listings ADD COLUMN showcase_images TEXT DEFAULT NULL AFTER description_images");
    echo "\nAdded showcase_images column\n";
}catch(Throwable $e){echo "\nshowcase: ".$e->getMessage()."\n";}
