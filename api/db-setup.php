<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/payos.php';
header('Content-Type: application/json');

// Test payOS connection
$orderCode = intval((time() - 1700000000) * 100 + 99);
$result = payosCreateLink(
    $orderCode,
    2000,
    'Test ShipperShop',
    [['name' => 'Test payment', 'quantity' => 1, 'price' => 2000]]
);

echo json_encode([
    'test' => 'payOS connection',
    'order_code' => $orderCode,
    'result' => $result,
    'has_checkout_url' => isset($result['checkoutUrl']),
    'has_qr' => isset($result['qrCode']),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
