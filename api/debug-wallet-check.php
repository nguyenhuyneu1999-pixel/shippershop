<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate exact wallet-api.php request
$_GET['action'] = 'plans';
$_SERVER['REQUEST_METHOD'] = 'GET';

try {
    require '/home/nhshiw2j/public_html/api/wallet-api.php';
} catch (Throwable $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
