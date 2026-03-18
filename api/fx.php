<?php
require_once '/home/nhshiw2j/public_html/includes/db.php';
$d=db();
$d->query("UPDATE `groups` SET banner_color = '#7C3AED' WHERE banner_color = '#EE4D2D'");
$d->query("ALTER TABLE `groups` MODIFY banner_color VARCHAR(20) DEFAULT '#7C3AED'");
// Add banner_image if not exists
try{$d->query("ALTER TABLE `groups` ADD COLUMN banner_image VARCHAR(500) DEFAULT NULL AFTER cover_image");}catch(Exception $e){}
echo "Done. Updated ".($d->fetchOne("SELECT COUNT(*) as c FROM `groups` WHERE banner_color='#7C3AED'")['c'])." groups to purple\n";
$cols=$d->fetchAll("SHOW COLUMNS FROM `groups`");
foreach($cols as $c) if(in_array($c['Field'],['banner_color','banner_image','cover_image','icon_image'])) echo $c['Field'].": ".$c['Type']." default=".$c['Default']."\n";
