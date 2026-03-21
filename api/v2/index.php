<?php
// ShipperShop API v2 Router — /api/v2/index.php
// Single entry point: /api/v2/?endpoint=posts&action=feed
// Also supports direct file access: /api/v2/posts.php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$endpoint = $_GET['endpoint'] ?? '';
if (!$endpoint) {
    // API index — list available endpoints
    // Auto-detect all API files
    $files = glob(__DIR__ . '/*.php');
    $endpoints = [];
    foreach ($files as $f) {
        $name = basename($f, '.php');
        if ($name === 'index') continue;
        $endpoints[$name] = '/api/v2/' . $name . '.php';
    }
    ksort($endpoints);

    echo json_encode([
        'api' => 'ShipperShop API v2',
        'version' => '4.2.0',
        'endpoint_count' => count($endpoints),
        'endpoints' => $endpoints,
        'docs' => 'https://shippershop.vn/docs/API.md',
        'health' => '/api/v2/health.php',
        'status' => '/api/v2/status.php',
        'test_suite' => '/api/test-suite.php?key=...',
    ], JSON_PRETTY_PRINT);
    exit;
}

// Route to endpoint file
$file = __DIR__ . '/' . preg_replace('/[^a-z0-9_-]/', '', $endpoint) . '.php';
if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $endpoint]);
    exit;
}

require $file;
