<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

echo "=== group_posts columns ===\n";
$cols = $d->fetchAll("SHOW COLUMNS FROM group_posts");
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== Current posts per group ===\n";
$stats = $d->fetchAll("SELECT group_id, COUNT(*) c FROM group_posts WHERE status='active' GROUP BY group_id ORDER BY group_id");
foreach ($stats as $s) echo "  Group #{$s['group_id']}: {$s['c']} posts\n";

echo "\n=== Sample group post ===\n";
$sample = $d->fetchOne("SELECT * FROM group_posts WHERE status='active' ORDER BY id DESC LIMIT 1");
print_r($sample);

echo "\n=== Groups table ===\n";
$groups = $d->fetchAll("SELECT id, name, category FROM `groups` WHERE status='active' ORDER BY id");
foreach ($groups as $g) echo "  #{$g['id']} {$g['name']} [{$g['category']}]\n";

echo "\n=== Available real images ===\n";
$imgs = glob('/home/nhshiw2j/public_html/uploads/posts/real/*.jpg');
echo count($imgs) . " real images\n";
$seeds = glob('/home/nhshiw2j/public_html/uploads/posts/seed_v2_*.jpg');
echo count($seeds) . " seed images\n";
