<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check for syntax errors by tokenizing
$file = '/home/nhshiw2j/public_html/api/wallet-api.php';
$code = file_get_contents($file);
echo "File size: " . strlen($code) . " bytes\n";

// Try token_get_all to find syntax issues
try {
    $tokens = token_get_all($code);
    echo "Tokens: " . count($tokens) . " (syntax OK if this shows)\n";
} catch (Throwable $e) {
    echo "Token error: " . $e->getMessage() . "\n";
}

// Check if file on server matches expected content
echo "First 50 chars: " . substr($code, 0, 50) . "\n";
echo "Has wAuth: " . (strpos($code, 'function wAuth') !== false ? 'YES' : 'NO') . "\n";
echo "Has getPdo: " . (strpos($code, 'getPdo') !== false ? 'YES' : 'NO') . "\n";
echo "Has getConnection: " . (strpos($code, 'getConnection') !== false ? 'YES' : 'NO') . "\n";
echo "Has hash_equals: " . (strpos($code, 'hash_equals') !== false ? 'YES' : 'NO') . "\n";

// Try eval-like approach with output buffering
ob_start();
$result = shell_exec('php -l ' . escapeshellarg($file) . ' 2>&1');
ob_end_clean();
echo "PHP lint: " . $result . "\n";
