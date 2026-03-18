<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Simulate wallet-api environment
$_GET['action'] = 'plans';
$_SERVER['REQUEST_METHOD'] = 'GET';

define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
require_once '/home/nhshiw2j/public_html/includes/functions.php';

echo "Step 1: includes loaded\n";

$db = db();
echo "Step 2: db() OK\n";

// Run migration section manually
try {
    $pdo = db()->getConnection();
    echo "Step 3: getConnection() OK\n";
    
    // Test the wallets table check
    $walletCols = array_column($pdo->query("SHOW COLUMNS FROM wallets")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    echo "Step 4: wallets columns = " . implode(',', $walletCols) . "\n";
    
} catch (Throwable $e) {
    echo "ERROR at migration: " . $e->getMessage() . " (line " . $e->getLine() . ")\n";
}

// Test plans query
try {
    $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY sort_order ASC", []);
    echo "Step 5: plans count = " . count($plans) . "\n";
} catch (Throwable $e) {
    echo "ERROR at plans: " . $e->getMessage() . "\n";
}

echo "DONE\n";
