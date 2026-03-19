<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Get existing images from uploads
$imgDir='/home/nhshiw2j/public_html/uploads/';
$seedImgs=glob($imgDir.'posts/seed_*.jpg');
echo "Available seed images: ".count($seedImgs)."\n";

// Pick 5 random images for showcase
$showcase=[];
if(count($seedImgs)>=5){
    $keys=array_rand($seedImgs,5);
    foreach($keys as $k){
        $showcase[]='/uploads/posts/'.basename($seedImgs[$k]);
    }
}
echo "Showcase images: ".json_encode($showcase)."\n";

// Update listing id=5 with showcase
$d->query("UPDATE marketplace_listings SET showcase_images=?, description_images=? WHERE id=5",
    [json_encode($showcase), json_encode(array_slice($showcase,0,3))]);
echo "Updated listing 5\n";

// Verify
$item=$d->fetchOne("SELECT showcase_images,description_images,video_url FROM marketplace_listings WHERE id=5");
echo "showcase_images: ".$item['showcase_images']."\n";
echo "description_images: ".$item['description_images']."\n";

// Also update other listings if exist
$listings=$d->fetchAll("SELECT id FROM marketplace_listings WHERE `status`='active'");
echo "\nAll active listings: ".count($listings)."\n";
foreach($listings as $l){
    if($l['id']==5) continue;
    $imgs2=[];
    $keys2=array_rand($seedImgs, min(3, count($seedImgs)));
    if(!is_array($keys2))$keys2=[$keys2];
    foreach($keys2 as $k){$imgs2[]='/uploads/posts/'.basename($seedImgs[$k]);}
    $d->query("UPDATE marketplace_listings SET showcase_images=? WHERE id=? AND (showcase_images IS NULL OR showcase_images='')",
        [json_encode($imgs2),$l['id']]);
}
echo "Updated all listings with showcase images\n";
echo "DONE\n";
