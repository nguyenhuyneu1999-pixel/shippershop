<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: text/plain");

echo "1. Include config\n";
require_once __DIR__ . '/../includes/config.php';

echo "2. Include db\n";
require_once __DIR__ . '/../includes/db.php';

echo "3. DB connect\n";
$d = db();

echo "4. Query posts\n";
$posts = $d->fetchAll("SELECT p.id, p.content, p.likes_count FROM posts p WHERE p.`status` = 'active' ORDER BY p.created_at DESC LIMIT 3");

echo "5. Results: " . count($posts) . " posts\n";
foreach ($posts as $p) {
    echo "  #" . $p['id'] . ": " . mb_substr($p['content'], 0, 40) . "...\n";
}

echo "\n6. Check posts.php includes\n";
$postsCode = file_get_contents(__DIR__ . '/posts.php');
echo "posts.php size: " . strlen($postsCode) . " bytes\n";

// Check for syntax errors
echo "\n7. Check auth-check.php\n";
require_once __DIR__ . '/auth-check.php';
echo "auth-check OK\n";

echo "\n8. Check if push-helper exists\n";
echo file_exists(__DIR__ . '/../includes/push-helper.php') ? "push-helper EXISTS\n" : "push-helper MISSING\n";

echo "\n9. Check if xp-helper exists\n";  
echo file_exists(__DIR__ . '/../includes/xp-helper.php') ? "xp-helper EXISTS\n" : "xp-helper MISSING\n";
