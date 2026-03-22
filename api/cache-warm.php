<?php
/**
 * ShipperShop Cache Warmer — Pre-cache hot endpoints
 * Chạy mỗi 5 phút để đảm bảo cache luôn warm
 * URL: /api/cache-warm.php?key=ss_cache_warm_key
 */
if (($_GET['key'] ?? '') !== 'ss_cache_warm_key') {
    http_response_code(403);
    echo json_encode(['error' => 'key']);
    exit;
}

header('Content-Type: application/json');
$start = microtime(true);
$results = [];

// Warm core endpoints
$endpoints = [
    ['url' => 'https://shippershop.vn/api/posts.php?limit=20', 'name' => 'feed_page1'],
    ['url' => 'https://shippershop.vn/api/posts.php?limit=20&page=2', 'name' => 'feed_page2'],
    ['url' => 'https://shippershop.vn/api/traffic.php', 'name' => 'traffic'],
    ['url' => 'https://shippershop.vn/api/marketplace.php', 'name' => 'marketplace'],
    ['url' => 'https://shippershop.vn/api/groups.php?action=discover', 'name' => 'groups_discover'],
    ['url' => 'https://shippershop.vn/api/groups.php?action=categories', 'name' => 'groups_categories'],
];

foreach ($endpoints as $ep) {
    $t = microtime(true);
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true]]);
    $response = @file_get_contents($ep['url'], false, $ctx);
    $ms = round((microtime(true) - $t) * 1000);
    $status = $response !== false ? 'ok' : 'fail';
    $results[] = [
        'name' => $ep['name'],
        'status' => $status,
        'ms' => $ms,
        'size' => $response ? strlen($response) : 0
    ];
}

$totalMs = round((microtime(true) - $start) * 1000);
$okCount = count(array_filter($results, function($r) { return $r['status'] === 'ok'; }));

echo json_encode([
    'success' => true,
    'warmed' => $okCount,
    'total' => count($results),
    'total_ms' => $totalMs,
    'results' => $results,
    'timestamp' => date('c')
], JSON_UNESCAPED_UNICODE);
