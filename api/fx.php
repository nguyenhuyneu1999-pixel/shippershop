<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

echo "Step 1: config\n";
require_once __DIR__.'/../includes/config.php';
echo "Step 2: db\n";
require_once __DIR__.'/../includes/db.php';
echo "Step 3: functions\n";
require_once __DIR__.'/../includes/functions.php';
echo "Step 4: cache\n";
require_once __DIR__.'/../includes/cache.php';
echo "Step 5: rate-limiter\n";
require_once __DIR__.'/../includes/rate-limiter.php';
echo "Step 6: validator\n";
require_once __DIR__.'/../includes/validator.php';
echo "Step 7: auth-v2\n";
require_once __DIR__.'/../includes/auth-v2.php';
echo "Step 8: DB connect\n";
$d=db();$pdo=$d->getConnection();
echo "Step 9: functions defined\n";

// Check for function conflicts
echo "http_get exists: " . (function_exists('http_get') ? 'YES' : 'NO') . "\n";
echo "http_get_ctx exists: " . (function_exists('http_get_ctx') ? 'YES' : 'NO') . "\n";
echo "t exists: " . (function_exists('t') ? 'YES' : 'NO') . "\n";

echo "Step 10: Check error_logs\n";
$errors = $d->fetchAll("SELECT message,file,line,created_at FROM error_logs ORDER BY created_at DESC LIMIT 5");
foreach($errors as $e) {
    echo "  ERROR: " . $e['message'] . " @ " . $e['file'] . ":" . $e['line'] . " (" . $e['created_at'] . ")\n";
}

echo "\nALL OK\n";
