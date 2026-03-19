<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
$posts=$d->fetchAll("SELECT id,images FROM posts WHERE images IS NOT NULL AND images!='' AND images!='[]' AND `status`='active' LIMIT 50");
$bad=0;
foreach($posts as $p){
    $imgs=json_decode($p['images'],true);
    if(!$imgs)continue;
    foreach($imgs as $img){
        $path='/home/nhshiw2j/public_html'.$img;
        if(!file_exists($path)){
            echo "Post ".$p['id'].": MISSING $img\n";
            $bad++;
            if($bad>=10){echo "...(showing first 10 only)\n";break 2;}
        }
    }
}
echo "\nBad images found: $bad (of ".count($posts)." posts checked)\n";
