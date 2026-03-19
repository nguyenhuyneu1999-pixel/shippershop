<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== ACTUAL double-path check ===\n";
// The LIKE '%/uploads%/uploads%' matches posts with 2+ images (each has /uploads path)
// Real double-path is: /uploads/posts//uploads/posts/
$real=$d->fetchAll("SELECT id,images FROM posts WHERE images LIKE '%uploads/posts/%uploads/posts/%' OR images LIKE '%uploads/posts//uploads%'");
echo "Real double-path: ".count($real)."\n";
foreach($real as $r){
    echo "  id=".$r['id'].": ".substr($r['images'],0,100)."\n";
}

echo "\n=== Verify all images are accessible ===\n";
// Sample 5 random posts with images
$samples=$d->fetchAll("SELECT id,images FROM posts WHERE images IS NOT NULL AND images != '' AND images != '[]' ORDER BY RAND() LIMIT 5");
foreach($samples as $s){
    $imgs=json_decode($s['images'],true);
    if(!$imgs)continue;
    foreach($imgs as $img){
        $path='/home/nhshiw2j/public_html'.$img;
        $exists=file_exists($path)?'✅':'❌';
        echo "  post=".$s['id']." $exists $img\n";
    }
}
echo "\nDONE\n";
