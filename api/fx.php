<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
echo "Total group_posts: ".$d->fetchOne("SELECT COUNT(*) as c FROM group_posts")['c']."\n";
echo "Cols: ";
$cols=$d->fetchAll("SHOW COLUMNS FROM group_posts");
foreach($cols as $c) echo $c['Field']."(".$c['Type']."), ";
echo "\n\nGroups:\n";
$gs=$d->fetchAll("SELECT id,name,slug,category_id,member_count FROM `groups` WHERE `status`='active' ORDER BY id");
foreach($gs as $g) echo $g['id']." | ".$g['name']." | members=".$g['member_count']."\n";
echo "\ngroup_post_likes cols: ";
$cols2=$d->fetchAll("SHOW COLUMNS FROM group_post_likes");
foreach($cols2 as $c) echo $c['Field']."(".$c['Type']."), ";
echo "\ngroup_post_comments cols: ";
$cols3=$d->fetchAll("SHOW COLUMNS FROM group_post_comments");
foreach($cols3 as $c) echo $c['Field']."(".$c['Type']."), ";
