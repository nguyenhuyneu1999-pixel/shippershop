<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$db = db();

echo "SHOW COLUMNS:\n";
$cols = $db->fetchAll("SHOW COLUMNS FROM posts", []);
foreach ($cols as $c) echo "  " . $c['Field'] . " " . $c['Type'] . " " . ($c['Null']=='YES'?'NULL':'NOT NULL') . " default=" . ($c['Default']??'none') . "\n";

echo "\nTEST INSERT with insert():\n";
try {
    $pid = $db->insert('posts', [
        'user_id' => 2,
        'content' => 'Test seed post',
        'type' => 'post',
        'status' => 'active'
    ]);
    echo "insert() returned: " . var_export($pid, true) . "\n";
} catch (Throwable $e) {
    echo "insert() error: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}

echo "\nTEST INSERT with query():\n";
try {
    $db->query("INSERT INTO posts (user_id, content, type, `status`) VALUES (?, ?, ?, ?)", [2, 'Test query post', 'post', 'active']);
    echo "query() OK, id=" . $db->getLastInsertId() . "\n";
} catch (Throwable $e) {
    echo "query() error: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}
