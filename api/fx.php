<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: text/plain');
$db = db();

// Add location columns to posts
$cols = ['province VARCHAR(100) NULL', 'district VARCHAR(100) NULL', 'ward VARCHAR(100) NULL'];
foreach ($cols as $col) {
    $name = explode(' ', $col)[0];
    try {
        $db->query("ALTER TABLE posts ADD COLUMN $col AFTER video_url", []);
        echo "Added: $name\n";
    } catch (Throwable $e) {
        echo "Skip $name: " . $e->getMessage() . "\n";
    }
}
echo "DONE";
