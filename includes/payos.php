<?php
// payOS Payment Gateway Integration for ShipperShop
// Docs: https://payos.vn/docs/api/

// ============================================
// CONFIGURATION - Replace with your payOS keys
// ============================================
// Register at https://my.payos.vn (5 min, CCCD only)
// Then create a payment channel and get these 3 keys:

define('PAYOS_CLIENT_ID', '');      // ← Paste your Client ID
define('PAYOS_API_KEY', '');        // ← Paste your API Key  
define('PAYOS_CHECKSUM_KEY', '');   // ← Paste your Checksum Key

define('PAYOS_API_URL', 'https://api-merchant.payos.vn');
define('PAYOS_RETURN_URL', 'https://shippershop.vn/wallet.html?payment=success');
define('PAYOS_CANCEL_URL', 'https://shippershop.vn/wallet.html?payment=cancel');

// ============================================
// CREATE PAYMENT LINK
// ============================================
function payosCreatePayment($orderCode, $amount, $description, $buyerName = '', $items = []) {
    // Signature = HMAC_SHA256(checksum_key, "amount=X&cancelUrl=X&description=X&orderCode=X&returnUrl=X")
    $sigData = "amount=" . $amount 
        . "&cancelUrl=" . PAYOS_CANCEL_URL 
        . "&description=" . $description 
        . "&orderCode=" . $orderCode 
        . "&returnUrl=" . PAYOS_RETURN_URL;
    
    $signature = hash_hmac('sha256', $sigData, PAYOS_CHECKSUM_KEY);
    
    $body = [
        'orderCode' => (int)$orderCode,
        'amount' => (int)$amount,
        'description' => $description,
        'cancelUrl' => PAYOS_CANCEL_URL,
        'returnUrl' => PAYOS_RETURN_URL,
        'signature' => $signature,
    ];
    
    if ($buyerName) $body['buyerName'] = $buyerName;
    if (!empty($items)) $body['items'] = $items;
    
    // Expire in 30 minutes
    $body['expiredAt'] = time() + 1800;
    
    $result = payosRequest('POST', '/v2/payment-requests', $body);
    
    if ($result && isset($result['code']) && $result['code'] === '00') {
        return [
            'success' => true,
            'checkoutUrl' => $result['data']['checkoutUrl'],
            'qrCode' => $result['data']['qrCode'] ?? '',
            'paymentLinkId' => $result['data']['paymentLinkId'],
            'accountNumber' => $result['data']['accountNumber'] ?? '',
            'accountName' => $result['data']['accountName'] ?? '',
            'amount' => $result['data']['amount'],
            'orderCode' => $result['data']['orderCode'],
        ];
    }
    
    return [
        'success' => false,
        'error' => $result['desc'] ?? 'Unknown error',
        'raw' => $result
    ];
}

// ============================================
// GET PAYMENT INFO
// ============================================
function payosGetPayment($orderCode) {
    $result = payosRequest('GET', '/v2/payment-requests/' . $orderCode);
    
    if ($result && isset($result['code']) && $result['code'] === '00') {
        return [
            'success' => true,
            'status' => $result['data']['status'], // PENDING, PAID, CANCELLED, EXPIRED
            'amount' => $result['data']['amount'],
            'amountPaid' => $result['data']['amountPaid'] ?? 0,
            'orderCode' => $result['data']['orderCode'],
        ];
    }
    
    return ['success' => false, 'error' => $result['desc'] ?? 'Unknown'];
}

// ============================================
// CANCEL PAYMENT
// ============================================
function payosCancelPayment($orderCode, $reason = 'Cancelled by user') {
    $result = payosRequest('POST', '/v2/payment-requests/' . $orderCode . '/cancel', [
        'cancellationReason' => $reason
    ]);
    return ($result && isset($result['code']) && $result['code'] === '00');
}

// ============================================
// VERIFY WEBHOOK SIGNATURE
// ============================================
function payosVerifyWebhook($data) {
    if (!isset($data['data']) || !isset($data['signature'])) return false;
    
    $d = $data['data'];
    
    // Build signature string from sorted fields
    $fields = [];
    foreach ($d as $key => $value) {
        $fields[$key] = $value;
    }
    ksort($fields);
    
    $sigParts = [];
    foreach ($fields as $k => $v) {
        $sigParts[] = $k . '=' . $v;
    }
    $sigData = implode('&', $sigParts);
    
    $expectedSig = hash_hmac('sha256', $sigData, PAYOS_CHECKSUM_KEY);
    
    return hash_equals($expectedSig, $data['signature']);
}

// ============================================
// HTTP REQUEST HELPER (no cURL needed)
// ============================================
function payosRequest($method, $path, $body = null) {
    $url = PAYOS_API_URL . $path;
    
    $headers = [
        'x-client-id: ' . PAYOS_CLIENT_ID,
        'x-api-key: ' . PAYOS_API_KEY,
        'Content-Type: application/json',
    ];
    
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'timeout' => 30,
            'ignore_errors' => true,
        ]
    ];
    
    if ($body !== null) {
        $opts['http']['content'] = json_encode($body);
    }
    
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) return null;
    
    return json_decode($response, true);
}

// ============================================
// GENERATE UNIQUE ORDER CODE
// ============================================
function payosOrderCode($userId, $planId) {
    // payOS requires int orderCode, max ~2^53
    // Format: UUUUPPPTTTTTT (user 4 digits + plan 3 digits + timestamp 6 digits)
    $ts = intval(substr(strval(time()), -6));
    return intval($userId) * 1000000000 + intval($planId) * 1000000 + $ts;
}
