<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Register shutdown to catch fatal errors
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "\n\nFATAL: " . json_encode($e, JSON_PRETTY_PRINT);
    }
});

$_GET['action'] = 'plans';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
try {
    include '/home/nhshiw2j/public_html/api/wallet-api.php';
} catch (Throwable $e) {
    echo "CAUGHT: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
}
$out = ob_get_clean();

header('Content-Type: text/plain');
echo "OUTPUT LENGTH: " . strlen($out) . "\n";
echo "HTTP CODE: " . http_response_code() . "\n";
echo "OUTPUT:\n" . substr($out, 0, 2000) . "\n";
