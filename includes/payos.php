<?php
// payOS Payment Helper for ShipperShop
require_once __DIR__ . '/payos-config.php';

function payosCreateLink($orderCode, $amount, $description, $items = []) {
    if (empty(PAYOS_CLIENT_ID) || empty(PAYOS_API_KEY) || empty(PAYOS_CHECKSUM_KEY)) {
        return ['error' => 'payOS keys not configured'];
    }
    $data = [
        'orderCode' => intval($orderCode),
        'amount' => intval($amount),
        'description' => mb_substr($description, 0, 25),
        'returnUrl' => PAYOS_RETURN_URL,
        'cancelUrl' => PAYOS_CANCEL_URL,
    ];
    if (!empty($items)) $data['items'] = $items;
    
    $signData = 'amount=' . $data['amount'] . '&cancelUrl=' . $data['cancelUrl'] . '&description=' . $data['description'] . '&orderCode=' . $data['orderCode'] . '&returnUrl=' . $data['returnUrl'];
    $data['signature'] = hash_hmac('sha256', $signData, PAYOS_CHECKSUM_KEY);
    
    $r = payosHTTP('POST', '/v2/payment-requests', $data);
    if ($r && isset($r['code']) && $r['code'] === '00') return $r['data'];
    return ['error' => $r['desc'] ?? ($r['error'] ?? 'Payment link creation failed')];
}

function payosGetPayment($orderCode) {
    if (empty(PAYOS_CLIENT_ID)) return null;
    $r = payosHTTP('GET', '/v2/payment-requests/' . $orderCode);
    return ($r && isset($r['code']) && $r['code'] === '00') ? $r['data'] : null;
}

function payosVerifyWebhook($body) {
    if (empty(PAYOS_CHECKSUM_KEY)) return false;
    $data = $body['data'] ?? [];
    $sig = $body['signature'] ?? '';
    if (empty($data) || empty($sig)) return false;
    $sorted = $data;
    ksort($sorted);
    $parts = [];
    foreach ($sorted as $k => $v) { $parts[] = $k . '=' . ($v === null ? '' : $v); }
    return hash_equals(hash_hmac('sha256', implode('&', $parts), PAYOS_CHECKSUM_KEY), $sig);
}

function payosOrderCode($userId, $planId) {
    // Max safe integer: 9007199254740991
    // Format: YYYYMMDD + HHMMSS + userId(4 digits) = 18 digits max
    // Simplified: timestamp_offset * 10000 + userId * 10 + planId
    $ts = time() - 1700000000; // ~74M seconds, fits in 8 digits
    return intval($ts * 10000 + ($userId % 1000) * 10 + ($planId % 10));
}

function payosHTTP($method, $path, $body = null) {
    $url = PAYOS_API_URL . $path;
    $h = "Content-Type: application/json\r\nx-client-id: " . PAYOS_CLIENT_ID . "\r\nx-api-key: " . PAYOS_API_KEY;
    $opts = ['http' => ['method' => $method, 'header' => $h, 'timeout' => 30, 'ignore_errors' => true]];
    if ($body !== null && $method === 'POST') $opts['http']['content'] = json_encode($body);
    $result = @file_get_contents($url, false, stream_context_create($opts));
    return $result ? json_decode($result, true) : ['error' => 'Connection failed'];
}
