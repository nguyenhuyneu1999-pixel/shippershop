<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$db=db();
$vids=['/uploads/videos/vid_3_1772440538_b41b51fe.mp4','/uploads/videos/vid_2_1772428966_bc9dc62b.mp4','/uploads/videos/vid_3_1772415179_e1326d96.mp4','/uploads/videos/vid_3_1772474536_9349ff13.mp4','/uploads/videos/vid_3_1773586906_b1a3e8ec.mp4','/uploads/videos/vid_3_1772415221_6af5c583.mp4','/uploads/videos/vid_2_1772418315_7eb1635e.mp4'];
// Add video to ~15 random posts that have no video
$posts=$db->fetchAll("SELECT id,content FROM posts WHERE `status`='active' AND (video_url IS NULL OR video_url='') ORDER BY RAND() LIMIT 15",[]);
$c=0;
foreach($posts as $p){
    $db->query("UPDATE posts SET video_url=? WHERE id=?",[$vids[array_rand($vids)],$p['id']]);
    $c++;
    echo "Video: [{$p['id']}] ".mb_substr($p['content'],0,40)."...
";
}
echo "
Added video to {$c} posts
";
$withVid=$db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE `status`='active' AND video_url IS NOT NULL AND video_url != ''",[]);
echo "Total with video: {$withVid['c']}
";
