<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$action = $_GET['action'] ?? 'detect';
$tokenFile = sys_get_temp_dir() . '/ss_fb_token';

// Action: store token (called with short token ref)
if ($action === 'store') {
    $raw = file_get_contents('php://input');
    if (strlen($raw) > 50) {
        file_put_contents($tokenFile, $raw);
        echo json_encode(['success' => true, 'stored' => strlen($raw) . ' bytes']);
    } else {
        echo json_encode(['error' => 'Token too short']);
    }
    exit;
}

// Read stored token
$token = file_exists($tokenFile) ? trim(file_get_contents($tokenFile)) : '';
if (empty($token)) { echo json_encode(['error' => 'No token stored. POST token to ?action=store first']); exit; }

if ($action === 'get_fb') {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    $d = db();
    $fb = $d->fetchOne("SELECT page_id, access_token, account_name FROM social_accounts WHERE platform = 'facebook' AND is_active = 1");
    echo json_encode($fb ?: ['error' => 'not configured']);
    exit;
}

if ($action === 'detect') {
    // Auto-detect page info from token
    $meResp = doGet("https://graph.facebook.com/v19.0/me?fields=id,name&access_token=" . urlencode($token));
    $me = json_decode($meResp, true);
    
    $pagesResp = doGet("https://graph.facebook.com/v19.0/me/accounts?fields=id,name,access_token&access_token=" . urlencode($token));
    $pages = json_decode($pagesResp, true);
    
    echo json_encode(['me' => $me, 'pages' => $pages], JSON_UNESCAPED_UNICODE);
    
} elseif ($action === 'save') {
    $pageId = $_GET['page_id'] ?? '';
    $pageName = $_GET['name'] ?? '';
    $pageToken = $_GET['page_token'] ?? $token;
    
    // If no page_id provided, auto-detect
    if (!$pageId) {
        $meResp = doGet("https://graph.facebook.com/v19.0/me?fields=id,name&access_token=" . urlencode($token));
        $me = json_decode($meResp, true);
        if (isset($me['id'])) {
            $pageId = $me['id'];
            $pageName = $me['name'] ?? 'Facebook Page';
        }
    }
    
    if (!$pageId) { echo json_encode(['error' => 'Cannot detect page ID']); exit; }
    
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
    // Get saved account
    $d = db();
    $fb = $d->fetchOne("SELECT * FROM social_accounts WHERE platform='facebook' AND is_active=1");
    if (!$fb) { echo json_encode(['error' => 'Chưa save token. Gọi ?action=save trước']); exit; }
    
    $msg = "🚀 ShipperShop - Cộng đồng Shipper Việt Nam\n\n💬 Nơi ae shipper chia sẻ kinh nghiệm, tips giao hàng, cảnh báo giao thông\n📱 Tham gia: shippershop.vn\n⏰ " . date('d/m/Y H:i');
    
    $url = "https://graph.facebook.com/v19.0/" . $fb['page_id'] . "/feed";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['message' => $msg, 'access_token' => $fb['access_token']]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) echo json_encode(['error' => 'CURL: ' . $err]);
    else echo $resp;
}

function doGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp ?: '{}';
}
