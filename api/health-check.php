<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== FIX DOUBLE-PATH IMAGES ===\n";
$bad=$d->fetchAll("SELECT id,images FROM posts WHERE images LIKE '%/uploads%/uploads%'");
echo "Found: ".count($bad)." posts\n";

$fixed=0;
foreach($bad as $p){
    $imgs=$p['images'];
    // Fix patterns like /uploads/posts//uploads/posts/seed_img_1.jpg
    $newImgs=str_replace('/uploads/posts//uploads/posts/','/uploads/posts/',$imgs);
    $newImgs=str_replace('/uploads/posts/uploads/posts/','/uploads/posts/',$newImgs);
    // Generic double /uploads
    $newImgs=preg_replace('#(/uploads/[^/]+/)(/uploads/[^/]+/)#','$2',$newImgs);
    if($newImgs!==$imgs){
        $d->query("UPDATE posts SET images=? WHERE id=?",[  $newImgs,$p['id']]);
        $fixed++;
    }
}
echo "Fixed: $fixed posts\n";

// Verify
$still=$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE images LIKE '%/uploads%/uploads%'");
echo "Remaining: ".$still['c']."\n";

if($still['c']>0){
    $samples=$d->fetchAll("SELECT id,LEFT(images,120) as img FROM posts WHERE images LIKE '%/uploads%/uploads%' LIMIT 3");
    foreach($samples as $s) echo "  id=".$s['id'].": ".$s['img']."\n";
}
echo "DONE\n";
