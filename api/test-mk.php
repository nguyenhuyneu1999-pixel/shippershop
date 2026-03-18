<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
header('Content-Type: text/plain');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$db = db();

// Check table structure
$cols = $db->fetchAll("SHOW COLUMNS FROM marketplace_listings", []);
echo "COLUMNS:\n";
foreach($cols as $c) echo "  " . $c['Field'] . " (" . $c['Type'] . ") " . ($c['Null']=='YES'?'NULL':'NOT NULL') . " default=" . ($c['Default']??'none') . "\n";

echo "\n";

// Try insert with error details
try {
    $db->query("INSERT INTO marketplace_listings (user_id,title,description,price,category,condition_type,images,location,phone,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())",
        [2, 'TEST', 'test desc', 5000, 'khac', 'good', '[]', 'HN', '0123', 'active']);
    echo "INSERT OK, id=" . $db->getLastInsertId() . "\n";
    $db->query("DELETE FROM marketplace_listings WHERE title='TEST' AND user_id=2", []);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    // Try raw PDO
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=nhshiw2j_shippershop', 'nhshiw2j_admin', '');
    } catch(Throwable $e2) {}
}

// Also try simpler insert
try {
    $db->query("INSERT INTO marketplace_listings (user_id,title,price,`status`,created_at) VALUES (?,?,?,?,NOW())",
        [2, 'SIMPLE TEST', 1000, 'active']);
    echo "SIMPLE INSERT OK, id=" . $db->getLastInsertId() . "\n";
    $db->query("DELETE FROM marketplace_listings WHERE title='SIMPLE TEST'", []);
} catch (Throwable $e) {
    echo "SIMPLE ERROR: " . $e->getMessage() . "\n";
}

echo "DONE!";
