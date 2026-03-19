<?php
header('Content-Type: application/json');
error_reporting(E_ALL);

$action = $_GET['action'] ?? 'info';
$token = '';

// Accept token from multiple sources
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    $token = $json['token'] ?? $_POST['token'] ?? '';
} else {
    $token = $_GET['token'] ?? '';
}

if (empty($token)) {
    echo json_encode(['error' => 'No token', 'method' => $_SERVER['REQUEST_METHOD'], 'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'none']);
    exit;
}

if ($action === 'info') {
    // First try /me to check token type
    $url = "https://graph.facebook.com/v19.0/me?fields=id,name&access_token=" . urlencode($token);
    $resp = doGet($url);
    $me = json_decode($resp, true);
    
    // Then get pages
    $url2 = "https://graph.facebook.com/v19.0/me/accounts?fields=id,name,access_token&access_token=" . urlencode($token);
    $resp2 = doGet($url2);
    $pages = json_decode($resp2, true);
    
    echo json_encode([
        'me' => $me,
        'pages' => $pages,
        'raw_me' => substr($resp, 0, 200),
        'raw_pages' => substr($resp2, 0, 500),
    ], JSON_UNESCAPED_UNICODE);
    
} elseif ($action === 'save') {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    $pageId = $input['page_id'] ?? $_GET['page_id'] ?? '';
    $pageName = $input['name'] ?? $_GET['name'] ?? 'Facebook Page';
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
    echo json_encode(['success' => true, 'page_id' => $pageId, 'name' => $pageName]);

} elseif ($action === 'test') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    $pageId = $input['page_id'] ?? $_GET['page_id'] ?? '';
    if (!$pageId) { echo json_encode(['error' => 'No page_id']); exit; }
    
    $msg = "🚀 ShipperShop - Cộng đồng Shipper Việt Nam\n📱 shippershop.vn\n⏰ " . date('d/m/Y H:i');
    $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => $msg, 'access_token' => $token]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    curl_close($ch);
    echo $resp ?: json_encode(['error' => 'Empty']);
}

function doGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) return json_encode(['curl_error' => $err]);
    return $resp;
}
