<?php
header('Content-Type: application/json');
$action = $_GET['action'] ?? $_POST['action'] ?? 'info';

// Accept token from POST body or GET
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? $_POST['token'] ?? $_GET['token'] ?? '';

if (empty($token)) { echo json_encode(['error' => 'No token provided']); exit; }

if ($action === 'info') {
    $url = "https://graph.facebook.com/v19.0/me/accounts?access_token=" . urlencode($token);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err) { echo json_encode(['error' => 'CURL: '.$err, 'http_code' => $code]); exit; }
    echo $resp ?: json_encode(['error' => 'Empty response', 'http_code' => $code]);
} elseif ($action === 'save') {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    $pageId = $input['page_id'] ?? '';
    $pageName = $input['name'] ?? 'Facebook Page';
    $pageToken = $input['page_token'] ?? $token;
    if (!$pageId) { echo json_encode(['error' => 'No page_id']); exit; }
    $d = db();
    $existing = $d->fetchOne("SELECT id FROM social_accounts WHERE platform = 'facebook'");
    if ($existing) {
        $d->query("UPDATE social_accounts SET page_id=?, access_token=?, account_name=?, is_active=1, updated_at=NOW() WHERE id=?",
            [$pageId, $pageToken, $pageName, $existing['id']]);
    } else {
        $d->query("INSERT INTO social_accounts (platform, page_id, access_token, account_name) VALUES ('facebook', ?, ?, ?)",
            [$pageId, $pageToken, $pageName]);
    }
    echo json_encode(['success' => true, 'message' => 'Facebook Page saved!', 'page_id' => $pageId]);
} elseif ($action === 'test') {
    $pageId = $input['page_id'] ?? '';
    if (!$pageId) { echo json_encode(['error' => 'No page_id']); exit; }
    $msg = "🚀 ShipperShop - Cộng đồng Shipper Việt Nam\n\n📱 Tham gia ngay: shippershop.vn\n⏰ " . date('Y-m-d H:i:s');
    $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => $msg, 'access_token' => $token]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) { echo json_encode(['error' => 'CURL: '.$err]); exit; }
    echo $resp ?: json_encode(['error' => 'Empty response']);
}
