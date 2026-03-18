<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    require_once '/home/nhshiw2j/public_html/api/wallet-api.php';
} catch (Throwable $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
