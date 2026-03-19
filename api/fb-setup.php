<?php
header('Content-Type: application/json');
$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? 'info';

if (empty($token)) { echo json_encode(['error' => 'No token']); exit; }

if ($action === 'info') {
    // Get pages
    $url = "https://graph.facebook.com/v19.0/me/accounts?access_token=" . urlencode($token);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    curl_close($ch);
    echo $resp;
} elseif ($action === 'save') {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    $pageId = $_GET['page_id'] ?? '';
    $pageName = $_GET['name'] ?? 'Facebook Page';
    $pageToken = $_GET['page_token'] ?? $token;
    
    $d = db();
    $existing = $d->fetchOne("SELECT id FROM social_accounts WHERE platform = 'facebook'");
    if ($existing) {
        $d->query("UPDATE social_accounts SET page_id=?, access_token=?, account_name=?, is_active=1, updated_at=NOW() WHERE id=?",
            [$pageId, $pageToken, $pageName, $existing['id']]);
    } else {
        $d->query("INSERT INTO social_accounts (platform, page_id, access_token, account_name) VALUES ('facebook', ?, ?, ?)",
            [$pageId, $pageToken, $pageName]);
    }
    echo json_encode(['success' => true, 'message' => 'Saved!', 'page_id' => $pageId, 'name' => $pageName]);
} elseif ($action === 'test') {
    $pageId = $_GET['page_id'] ?? '';
    $msg = "🧪 Test auto-post từ ShipperShop Marketing Automation\n\n📱 shippershop.vn\n" . date('Y-m-d H:i:s');
    $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => $msg, 'access_token' => $token]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    curl_close($ch);
    echo $resp;
}
