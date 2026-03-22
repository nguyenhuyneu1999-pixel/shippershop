<?php
/**
 * ShipperShop Batch API — Multiple requests in one HTTP call
 * POST /api/batch.php
 * Body: { "requests": [{"url": "/api/posts.php?limit=5"}, {"url": "/api/notifications.php?action=unread_count"}] }
 * Max 5 requests per batch
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'message' => 'POST only']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$requests = $input['requests'] ?? [];

if (empty($requests) || count($requests) > 5) {
    echo json_encode(['success' => false, 'message' => 'Max 5 requests per batch']);
    exit;
}

// Forward auth header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$baseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'shippershop.vn');

$results = [];
$mh = curl_multi_init();
$handles = [];

foreach ($requests as $i => $req) {
    $url = $baseUrl . ($req['url'] ?? '');
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $authHeader ? ["Authorization: $authHeader"] : [],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_multi_add_handle($mh, $ch);
    $handles[$i] = $ch;
}

// Execute all in parallel
$running = 0;
do {
    curl_multi_exec($mh, $running);
    curl_multi_select($mh);
} while ($running > 0);

// Collect results
foreach ($handles as $i => $ch) {
    $body = curl_multi_getcontent($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $json = json_decode($body, true);
    $results[] = [
        'url' => $requests[$i]['url'] ?? '',
        'status' => $code,
        'data' => $json ?? $body,
    ];
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

echo json_encode(['success' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);
