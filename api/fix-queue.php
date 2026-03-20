<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();
$pdo = $d->getConnection();

echo "1. Clean titles\n";
$pdo->exec("UPDATE content_queue SET title = TRIM(SUBSTR(title, 4)) WHERE title LIKE 'FB:%' OR title LIKE 'TK:%'");

echo "2. Add hashtags to FB posts\n";
$fb = $d->fetchAll("SELECT id FROM content_queue WHERE type='facebook' AND `status`='pending' AND content NOT LIKE '%#shipper%'");
foreach ($fb as $f) {
    $d->query("UPDATE content_queue SET content = CONCAT(content, '\n\n#shipper #giaohang #congdongshipper #GHTK #GHN') WHERE id = ?", [$f['id']]);
}
echo "  Fixed " . count($fb) . " FB posts\n";

echo "3. Stats\n";
$stats = $d->fetchAll("SELECT type, `status`, COUNT(*) c FROM content_queue GROUP BY type, `status`");
foreach ($stats as $s) echo "  {$s['type']}/{$s['status']}: {$s['c']}\n";

echo "\nDone!\n";
