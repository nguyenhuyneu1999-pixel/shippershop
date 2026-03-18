<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
echo "=== groups table ===\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM `groups`");
foreach($cols as $c) echo $c['Field']." | ".$c['Type']." | ".$c['Default']."\n";
echo "\n=== group_categories ===\n";
$cats=$d->fetchAll("SELECT * FROM group_categories LIMIT 20");
foreach($cats as $c) echo $c['id']." | ".$c['slug']." | ".$c['name']." | ".$c['icon']."\n";
echo "\n=== groups sample ===\n";
$gs=$d->fetchAll("SELECT id,name,slug,category_id,member_count,icon_image,banner_image,banner_color,privacy FROM `groups` LIMIT 5");
foreach($gs as $g) echo json_encode($g,JSON_UNESCAPED_UNICODE)."\n";
echo "\n=== group_members count ===\n";
echo $d->fetchOne("SELECT COUNT(*) as c FROM group_members")['c']."\n";
