<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
// Add video_url column
try{
    $d->query("ALTER TABLE marketplace_listings ADD COLUMN video_url VARCHAR(500) DEFAULT NULL AFTER images");
    echo "Added video_url column\n";
}catch(Throwable $e){echo "video_url: ".$e->getMessage()."\n";}
// Add description_images column for rich description images
try{
    $d->query("ALTER TABLE marketplace_listings ADD COLUMN description_images TEXT DEFAULT NULL AFTER description");
    echo "Added description_images column\n";
}catch(Throwable $e){echo "desc_images: ".$e->getMessage()."\n";}
// Add phone column if missing
try{
    $d->query("ALTER TABLE marketplace_listings ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER location");
    echo "Added phone column\n";
}catch(Throwable $e){echo "phone: ".$e->getMessage()."\n";}
echo "DONE\n";
