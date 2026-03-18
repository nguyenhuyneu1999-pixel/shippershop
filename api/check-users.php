<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$db = db();
$cols = $db->fetchAll("SHOW COLUMNS FROM users", []);
foreach($cols as $c) echo $c['Field'] . " (" . $c['Type'] . ") " . ($c['Default']??'') . "\n";
echo "\n--- posts ---\n";
$cols2 = $db->fetchAll("SHOW COLUMNS FROM posts", []);
foreach($cols2 as $c) echo $c['Field'] . " (" . $c['Type'] . ") " . ($c['Default']??'') . "\n";
echo "\n--- comments ---\n";
$cols3 = $db->fetchAll("SHOW COLUMNS FROM comments", []);
foreach($cols3 as $c) echo $c['Field'] . " (" . $c['Type'] . ") " . ($c['Default']??'') . "\n";
