<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');
$_GET['key'] = 'ss_test_secret';
$_GET['page'] = 1;
echo "Starting...\n";
try {
    include __DIR__ . '/test-suite.php';
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
