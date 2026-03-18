<?php
set_time_limit(120);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$db=db();

// 1. Fix likes_count from post_likes table
$posts=$db->fetchAll("SELECT id FROM posts WHERE `status`='active'", []);
foreach($posts as $p){
    $cnt=$db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id=?",[$p['id']]);
    $db->query("UPDATE posts SET likes_count=? WHERE id=?",[intval($cnt['c']??0),$p['id']]);
}
echo "Fixed likes_count for ".count($posts)." posts\n";

// 2. Fix comments_count
foreach($posts as $p){
    $cnt=$db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE post_id=? AND `status`='active'",[$p['id']]);
    $db->query("UPDATE posts SET comments_count=? WHERE id=?",[intval($cnt['c']??0),$p['id']]);
}
echo "Fixed comments_count\n";

// 3. Ensure latest 30 posts have images  
$imgDir=__DIR__.'/../uploads/posts/';
$allImgs=[];
foreach(glob($imgDir.'theme_*.jpg') as $f) $allImgs[]=str_replace($imgDir,'/uploads/posts/',basename($f));
foreach(glob($imgDir.'seed_*.jpg') as $f) $allImgs[]='/uploads/posts/'.basename($f);
echo "Available: ".count($allImgs)." images\n";

$recent=$db->fetchAll("SELECT id,content FROM posts WHERE `status`='active' AND (images IS NULL OR images='[]' OR images='') ORDER BY created_at DESC LIMIT 30",[]);
$idx=0;
foreach($recent as $r){
    if(count($allImgs)<1) break;
    $n=rand(1,2);
    $sel=[];
    for($j=0;$j<$n;$j++){$sel[]='/uploads/posts/'.$allImgs[$idx%count($allImgs)];$idx++;}
    $db->query("UPDATE posts SET images=? WHERE id=?",[json_encode($sel),$r['id']]);
}
echo "Added images to ".count($recent)." recent posts\n";

echo "DONE\n";
