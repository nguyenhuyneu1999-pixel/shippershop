<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
// Fix double path in posts.images
$posts=$d->fetchAll("SELECT id,images FROM posts WHERE images LIKE '%/uploads/posts//uploads/%'");
echo "Posts with double path: ".count($posts)."\n";
foreach($posts as $p){
    $fixed=str_replace('/uploads/posts//uploads/posts/','/uploads/posts/',$p['images']);
    $d->query("UPDATE posts SET images=? WHERE id=?",[$fixed,$p['id']]);
    echo "  Fixed post ".$p['id'].": ".$fixed."\n";
}

// Check for any other bad paths
$bad=$d->fetchAll("SELECT id,images FROM posts WHERE images IS NOT NULL AND images NOT LIKE '%null%' AND images != ''");
$still=0;
foreach($bad as $p){
    $imgs=json_decode($p['images'],true);
    if(!$imgs)continue;
    foreach($imgs as $img){
        if(!file_exists('/home/nhshiw2j/public_html'.$img)){
            // Try to find the file
            $base=basename($img);
            if(file_exists('/home/nhshiw2j/public_html/uploads/posts/'.$base)){
                $newImgs=['/uploads/posts/'.$base];
                $d->query("UPDATE posts SET images=? WHERE id=?", [json_encode($newImgs),$p['id']]);
                echo "  Remapped post ".$p['id'].": ".$base."\n";
            }else{
                $still++;
            }
        }
    }
}
echo "\nStill missing after fix: $still\n";
echo "Done!\n";
