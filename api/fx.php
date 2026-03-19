<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
$imgDir='/home/nhshiw2j/public_html/uploads/posts/';

// Count actual files
$files=glob($imgDir.'seed_*.jpg');
echo "Seed image files: ".count($files)."\n";

// Check posts with missing images
$posts=$d->fetchAll("SELECT id,images FROM posts WHERE images IS NOT NULL AND images != 'null' AND images != ''");
$missing=0;$ok=0;$badPosts=[];
foreach($posts as $p){
    $imgs=json_decode($p['images'],true);
    if(!$imgs)continue;
    foreach($imgs as $img){
        $path='/home/nhshiw2j/public_html'.$img;
        if(!file_exists($path)){$missing++;$badPosts[]=$p['id'];}
        else $ok++;
    }
}
echo "Images OK: $ok, Missing: $missing\n";
if($missing>0){
    echo "Posts with missing images (first 10): ".implode(',',array_unique(array_slice($badPosts,0,10)))."\n";
    // Show sample missing paths
    foreach(array_slice(array_unique($badPosts),0,3) as $pid){
        $p=$d->fetchOne("SELECT images FROM posts WHERE id=?",[$pid]);
        echo "  Post $pid: ".$p['images']."\n";
    }
}

// Same for group_posts
$gposts=$d->fetchAll("SELECT id,images FROM group_posts WHERE images IS NOT NULL AND images != 'null' AND images != ''");
$gmissing=0;$gok=0;
foreach($gposts as $p){
    $imgs=json_decode($p['images'],true);
    if(!$imgs)continue;
    foreach($imgs as $img){
        $path='/home/nhshiw2j/public_html'.$img;
        if(!file_exists($path))$gmissing++;
        else $gok++;
    }
}
echo "Group images OK: $gok, Missing: $gmissing\n";
