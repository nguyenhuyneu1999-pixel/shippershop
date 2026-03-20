<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: text/plain");

// Simulate a GET request to posts.php
$_GET['limit'] = 3;
$_GET['sort'] = 'new';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "=== Loading posts.php ===\n";
ob_start();
try {
    include __DIR__ . '/posts.php';
} catch (Throwable $e) {
    echo "\n\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
$output = ob_get_clean();
echo "Output length: " . strlen($output) . "\n";
echo "Output:\n" . substr($output, 0, 500) . "\n";
