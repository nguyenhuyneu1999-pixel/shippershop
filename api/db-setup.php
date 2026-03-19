<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
echo "=== COLUMNS ===\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM `groups`");
foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";
echo "\n=== CURRENT GROUPS ===\n";
$groups=$d->fetchAll("SELECT id,name,description,avatar,cover_image,category,member_count,post_count FROM `groups` ORDER BY id");
foreach($groups as $g){
    echo "id={$g['id']} name={$g['name']} cat={$g['category']} members={$g['member_count']} posts={$g['post_count']} avatar={$g['avatar']} cover={$g['cover_image']}\n";
}
