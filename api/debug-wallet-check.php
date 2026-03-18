<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
header('Content-Type: text/plain');

echo "=== groups table ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM `groups`")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== group_members ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM group_members")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== group_posts ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM group_posts")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== Existing groups ===\n";
$g = $pdo->query("SELECT id,name,slug,category,member_count,post_count FROM `groups` ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($g as $r) echo "  #{$r['id']} {$r['name']} (cat={$r['category']}, m={$r['member_count']}, p={$r['post_count']})\n";
if (empty($g)) echo "  (empty)\n";

echo "\n=== Existing members ===\n";
$m = $pdo->query("SELECT gm.group_id, gm.user_id, gm.role, u.fullname FROM group_members gm JOIN users u ON gm.user_id=u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($m as $r) echo "  group#{$r['group_id']} user#{$r['user_id']} {$r['fullname']} ({$r['role']})\n";
if (empty($m)) echo "  (empty)\n";
