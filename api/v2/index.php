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
    echo json_encode([
        'api' => 'ShipperShop API v2',
        'version' => '2.0.0',
        'endpoints' => [
            'posts' => '/api/v2/posts.php',
            'messages' => '/api/v2/messages.php',
            'users' => '/api/v2/users.php',
            'groups' => '/api/v2/groups.php',
            'notifications' => '/api/v2/notifications.php',
            'search' => '/api/v2/search.php',
            'admin' => '/api/v2/admin.php',
            'wallet' => '/api/v2/wallet.php',
            'traffic' => '/api/v2/traffic.php',
            'marketplace' => '/api/v2/marketplace.php',
            'social' => '/api/v2/social.php',
            'gamification' => '/api/v2/gamification.php',
            'content' => '/api/v2/content.php',
            'push' => '/api/v2/push.php',
            'analytics' => '/api/v2/analytics.php',
            'health' => '/api/v2/health.php'
        ],
        'docs' => 'https://shippershop.vn/docs/API.md'
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
