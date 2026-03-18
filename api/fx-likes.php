<?php
set_time_limit(120);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$db=db();
$userIds=array_map(function($u){return intval($u['id']);}, $db->fetchAll("SELECT id FROM users WHERE id>10 LIMIT 100",[]));
$posts=$db->fetchAll("SELECT id FROM posts WHERE `status`='active' AND likes_count=0",[]);
echo "Posts with 0 likes: ".count($posts)."\n";
foreach($posts as $p){
    $n=rand(5,30);
    shuffle($userIds);
    foreach(array_slice($userIds,0,$n) as $uid){
        try{$db->query("INSERT IGNORE INTO post_likes(post_id,user_id,created_at) VALUES(?,?,NOW())",[$p['id'],$uid]);}catch(Throwable $e){}
    }
    $c=$db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE post_id=?",[$p['id']]);
    $db->query("UPDATE posts SET likes_count=? WHERE id=?",[intval($c['c']??0),$p['id']]);
}
echo "Done! Added likes to ".count($posts)." posts\n";
