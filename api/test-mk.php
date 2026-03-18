<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
header('Content-Type: text/plain');

echo "Step 1: before config\n";
require_once __DIR__ . '/../includes/config.php';
echo "Step 2: after config\n";
require_once __DIR__ . '/../includes/db.php';
echo "Step 3: after db\n";
require_once __DIR__ . '/../includes/functions.php';
echo "Step 4: after functions\n";

// Test mAuth-like code
$headers = getallheaders();
$h = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
echo "Auth header: $h\n";

if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
    $parts = explode('.', $m[1]);
    echo "JWT parts: " . count($parts) . "\n";
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        echo "Payload: " . print_r($payload, true) . "\n";
    }
}

// Test DB insert
try {
    $db = db();
    echo "Step 5: db OK\n";
    $db->query("INSERT INTO marketplace_listings (user_id,title,description,price,category,condition_type,images,location,phone,`status`,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())",
        [2, 'TEST', 'test desc', 5000, 'khac', 'good', '[]', 'HN', '0123', 'active']);
    $id = $db->getLastInsertId();
    echo "INSERTED id=$id\n";
    // Delete test
    $db->query("DELETE FROM marketplace_listings WHERE id=?", [$id]);
    echo "DELETED\n";
} catch (Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . " at line " . $e->getLine() . "\n";
}

echo "DONE!";
