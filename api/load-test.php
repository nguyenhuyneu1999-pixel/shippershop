<?php
/**
 * ShipperShop Load Test — Simulate concurrent requests
 * URL: /api/load-test.php?key=ss_load_test&concurrent=10
 */
if (($_GET['key'] ?? '') !== 'ss_load_test') { http_response_code(403); exit; }
set_time_limit(120);
header('Content-Type: application/json');

$concurrent = min(intval($_GET['concurrent'] ?? 10), 50);
$endpoints = [
    ['url' => '/api/posts.php?limit=5', 'name' => 'feed'],
    ['url' => '/api/groups.php?action=discover', 'name' => 'groups'],
    ['url' => '/api/traffic.php', 'name' => 'traffic'],
    ['url' => '/api/marketplace.php', 'name' => 'marketplace'],
    ['url' => '/api/wallet-api.php?action=plans', 'name' => 'wallet_plans'],
    ['url' => '/api/map-pins.php', 'name' => 'map'],
];

$results = [];
$totalStart = microtime(true);

foreach ($endpoints as $ep) {
    $times = [];
    $errors = 0;
    
    // Simulate concurrent requests sequentially (shared hosting can't do real parallel PHP)
    for ($i = 0; $i < $concurrent; $i++) {
        $t = microtime(true);
        $ctx = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
        $resp = @file_get_contents('https://shippershop.vn' . $ep['url'], false, $ctx);
        $ms = round((microtime(true) - $t) * 1000);
        
        if ($resp === false) {
            $errors++;
            $times[] = $ms;
        } else {
            $json = json_decode($resp, true);
            $times[] = $ms;
            if (!$json || !isset($json['success'])) $errors++;
        }
    }
    
    sort($times);
    $results[] = [
        'endpoint' => $ep['name'],
        'requests' => $concurrent,
        'errors' => $errors,
        'avg_ms' => round(array_sum($times) / count($times)),
        'min_ms' => $times[0],
        'max_ms' => end($times),
        'p95_ms' => $times[intval(count($times) * 0.95)] ?? end($times),
        'success_rate' => round(($concurrent - $errors) / $concurrent * 100, 1),
    ];
}

$totalMs = round((microtime(true) - $totalStart) * 1000);
$totalRequests = $concurrent * count($endpoints);
$rps = $totalMs > 0 ? round($totalRequests / ($totalMs / 1000), 1) : 0;

// Grade
$avgMs = round(array_sum(array_column($results, 'avg_ms')) / count($results));
$grade = $avgMs < 200 ? 'A' : ($avgMs < 500 ? 'B' : ($avgMs < 1000 ? 'C' : 'D'));
$errorRate = round(array_sum(array_column($results, 'errors')) / $totalRequests * 100, 1);

echo json_encode([
    'success' => true,
    'grade' => $grade,
    'summary' => [
        'total_requests' => $totalRequests,
        'concurrent_per_endpoint' => $concurrent,
        'total_time_ms' => $totalMs,
        'requests_per_second' => $rps,
        'avg_response_ms' => $avgMs,
        'error_rate' => $errorRate,
    ],
    'endpoints' => $results,
    'capacity_estimate' => $rps > 50 ? 'Good for ' . ($rps * 10) . '+ concurrent users' : 'Needs optimization',
    'tested_at' => date('c'),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
