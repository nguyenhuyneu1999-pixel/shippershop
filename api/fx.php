<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
echo "=== ALL groups ===\n";
$gs=$d->fetchAll("SELECT id,name,slug,category_id,member_count,icon_image,banner_image,banner_color,privacy,cover_image FROM `groups` WHERE `status`='active'");
foreach($gs as $g) echo $g['id']." | ".$g['name']." | cat=".$g['category_id']." | members=".$g['member_count']." | icon=".($g['icon_image']?'yes':'no')." | banner=".($g['banner_color']?$g['banner_color']:'none')." | cover=".($g['cover_image']?'yes':'no')."\n";
echo "\n=== groups.php API actions ===\n";
$f=file_get_contents('/home/nhshiw2j/public_html/api/groups.php');
preg_match_all("/action['\"]?\s*===?\s*['\"]([a-z_]+)/", $f, $m);
echo implode(", ", array_unique($m[1]))."\n";
echo "\n=== banner_image col type ===\n";
$col=$d->fetchOne("SHOW COLUMNS FROM `groups` WHERE Field='banner_image'");
echo ($col ? $col['Type'] : 'NOT EXISTS')."\n";
