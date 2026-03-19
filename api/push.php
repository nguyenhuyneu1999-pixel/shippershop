<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../includes/vapid_keys.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$d = db();
$action = $_GET['action'] ?? '';

// GET vapid public key (no auth needed)
if ($action === 'vapid_key') {
    echo json_encode(['success' => true, 'data' => ['publicKey' => VAPID_PUBLIC_KEY]]);
    exit;
}

// Auth required for all other actions
$uid = getAuthUserId();
if (!$uid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Subscribe
if ($action === 'subscribe') {
    $endpoint = $input['endpoint'] ?? '';
    $p256dh = $input['keys']['p256dh'] ?? ($input['p256dh'] ?? '');
    $auth = $input['keys']['auth'] ?? ($input['auth'] ?? '');
    
    if (!$endpoint || !$p256dh || !$auth) {
        echo json_encode(['success' => false, 'message' => 'Missing subscription data']);
        exit;
    }
    
    // Upsert: remove old subscription with same endpoint
    $d->query("DELETE FROM push_subscriptions WHERE endpoint = ?", [$endpoint]);
    $d->query("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)", 
        [$uid, $endpoint, $p256dh, $auth]);
    
    echo json_encode(['success' => true, 'message' => 'Subscribed']);
    exit;
}

// Unsubscribe
if ($action === 'unsubscribe') {
    $endpoint = $input['endpoint'] ?? '';
    if ($endpoint) {
        $d->query("DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?", [$uid, $endpoint]);
    } else {
        $d->query("DELETE FROM push_subscriptions WHERE user_id = ?", [$uid]);
    }
    echo json_encode(['success' => true, 'message' => 'Unsubscribed']);
    exit;
}

// Test push
if ($action === 'test') {
    require_once __DIR__ . '/../includes/push-helper.php';
    $sent = notifyUser($uid, 'ShipperShop', 'Thông báo test thành công! 🎉', 'general', '/');
    echo json_encode(['success' => true, 'message' => "Sent to $sent devices"]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
