<?php
/**
 * ShipperShop Push Subscription Management
 * POST: Save push subscription
 * DELETE: Remove subscription
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$d = db();

// Auth
$userId = null;
$h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
    $data = verifyJWT($m[1]);
    if ($data) $userId = intval($data['user_id']);
}
if (!$userId) { echo json_encode(['success' => false, 'message' => 'Auth required']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method === 'POST') {
    $endpoint = $input['endpoint'] ?? '';
    $p256dh = $input['keys']['p256dh'] ?? '';
    $auth = $input['keys']['auth'] ?? '';
    
    if (!$endpoint) {
        echo json_encode(['success' => false, 'message' => 'Missing endpoint']);
        exit;
    }
    
    // Upsert subscription
    $existing = $d->fetchOne("SELECT id FROM push_subscriptions WHERE user_id = ? AND endpoint = ?", [$userId, $endpoint]);
    if ($existing) {
        $d->query("UPDATE push_subscriptions SET p256dh = ?, auth_key = ?, updated_at = NOW() WHERE id = ?", [$p256dh, $auth, $existing['id']]);
    } else {
        $d->query("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth_key, created_at) VALUES (?, ?, ?, ?, NOW())", [$userId, $endpoint, $p256dh, $auth]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Subscription saved']);
    exit;
}

if ($method === 'DELETE') {
    $endpoint = $input['endpoint'] ?? '';
    if ($endpoint) {
        $d->query("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?", [$userId, $endpoint]);
    }
    echo json_encode(['success' => true, 'message' => 'Unsubscribed']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid method']);
