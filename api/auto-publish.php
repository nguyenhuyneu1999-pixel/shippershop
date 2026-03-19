<?php
/**
 * AUTO PUBLISHER
 * Kiểm tra queue và publish content đến các platform
 * 
 * ?action=run              - Publish tất cả pending items đến giờ
 * ?action=setup_facebook   - Hướng dẫn setup Facebook Page Token
 * ?action=save_token       - Lưu access token
 * ?action=test_post        - Test post 1 bài lên Facebook
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

define('MKT_KEY', 'ss_mkt_' . substr(md5(JWT_SECRET . 'marketing'), 0, 16));
$key = $_GET['key'] ?? '';
$action = $_GET['action'] ?? 'run';

// Public actions don't need key
if (!in_array($action, ['setup_facebook'])) {
    if ($key !== MKT_KEY) {
        // Try JWT auth for admin
        require_once __DIR__ . '/auth-check.php';
        $uid = getAuthUserId();
        $admin = db()->fetchOne("SELECT role FROM users WHERE id = ?", [$uid]);
        if (!$admin || $admin['role'] !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }
}

$d = db();

// ========================================
// RUN: Auto publish pending items
// ========================================
if ($action === 'run') {
    $results = ['time' => date('Y-m-d H:i:s'), 'items' => []];
    
    $pending = $d->fetchAll("SELECT * FROM content_queue WHERE `status` = 'pending' AND scheduled_at <= NOW() ORDER BY scheduled_at LIMIT 10");
    
    foreach ($pending as $item) {
        $result = publishItem($d, $item);
        $results['items'][] = $result;
    }
    
    $results['pending_count'] = (int)$d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status` = 'pending'")['c'];
    $results['published_today'] = (int)$d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status` = 'published' AND DATE(published_at) = CURDATE()")['c'];
    
    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// SETUP FACEBOOK: Instructions
// ========================================
if ($action === 'setup_facebook') {
    echo json_encode(['success' => true, 'data' => [
        'instructions' => [
            '1. Tạo Facebook App tại: https://developers.facebook.com/apps/',
            '2. Chọn "Business" type, thêm "Pages" product',
            '3. Trong Settings > Basic: lấy App ID + App Secret',
            '4. Vào Graph API Explorer: https://developers.facebook.com/tools/explorer/',
            '5. Chọn App → Get Token → Get Page Access Token',
            '6. Permissions cần: pages_manage_posts, pages_read_engagement',
            '7. Extend token: https://developers.facebook.com/tools/debug/ → Extend Access Token',
            '8. Gọi API save_token với page_id + access_token để lưu',
        ],
        'save_url' => '/api/auto-publish.php?action=save_token&key=YOUR_KEY',
        'save_body' => '{"platform":"facebook","page_id":"YOUR_PAGE_ID","access_token":"YOUR_LONG_LIVED_TOKEN","account_name":"ShipperShop"}',
    ]], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// SAVE TOKEN: Store social account credentials
// ========================================
if ($action === 'save_token' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $platform = $input['platform'] ?? '';
    $pageId = $input['page_id'] ?? '';
    $token = $input['access_token'] ?? '';
    $name = $input['account_name'] ?? $platform;
    
    if (!$platform || !$token) {
        echo json_encode(['success' => false, 'message' => 'Missing platform or access_token']);
        exit;
    }
    
    // Upsert
    $existing = $d->fetchOne("SELECT id FROM social_accounts WHERE platform = ?", [$platform]);
    if ($existing) {
        $d->query("UPDATE social_accounts SET page_id = ?, access_token = ?, account_name = ?, is_active = 1, updated_at = NOW() WHERE id = ?",
            [$pageId, $token, $name, $existing['id']]);
    } else {
        $d->query("INSERT INTO social_accounts (platform, page_id, access_token, account_name) VALUES (?, ?, ?, ?)",
            [$platform, $pageId, $token, $name]);
    }
    
    echo json_encode(['success' => true, 'message' => ucfirst($platform) . ' configured successfully!'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// TEST POST: Post 1 item to Facebook
// ========================================
if ($action === 'test_post') {
    $fb = $d->fetchOne("SELECT * FROM social_accounts WHERE platform = 'facebook' AND is_active = 1");
    if (!$fb || !$fb['access_token']) {
        echo json_encode(['success' => false, 'message' => 'Facebook not configured. Use ?action=setup_facebook for instructions']);
        exit;
    }
    
    $testMessage = "🧪 Test post from ShipperShop Marketing Automation\n\n" .
        "📱 Cộng đồng shipper Việt Nam: shippershop.vn\n" .
        date('Y-m-d H:i:s');
    
    $result = postToFacebook($fb['page_id'], $fb['access_token'], $testMessage);
    echo json_encode(['success' => $result['success'], 'data' => $result], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// PUBLISH FUNCTIONS
// ============================================================

function publishItem($d, $item) {
    $result = ['id' => $item['id'], 'type' => $item['type'], 'title' => $item['title']];
    
    switch ($item['type']) {
        case 'facebook':
            $fb = $d->fetchOne("SELECT * FROM social_accounts WHERE platform = 'facebook' AND is_active = 1");
            if ($fb && $fb['access_token'] && $fb['page_id']) {
                $resp = postToFacebook($fb['page_id'], $fb['access_token'], $item['content']);
                if ($resp['success']) {
                    markPublished($d, $item['id'], $resp['post_id'] ?? '');
                    $result['status'] = 'published';
                    $result['fb_post_id'] = $resp['post_id'] ?? '';
                } else {
                    markFailed($d, $item['id'], $resp['error'] ?? 'FB API error');
                    $result['status'] = 'failed';
                    $result['error'] = $resp['error'] ?? '';
                }
            } else {
                // No Facebook configured - save content for manual use
                markFailed($d, $item['id'], 'Facebook not configured');
                $result['status'] = 'saved_for_manual';
                $result['content_preview'] = mb_substr($item['content'], 0, 100);
            }
            break;
            
        case 'tiktok':
            // TikTok requires video - save script for manual use
            markFailed($d, $item['id'], 'TikTok: script saved, manual video needed');
            $result['status'] = 'script_saved';
            $result['script'] = mb_substr($item['content'], 0, 200);
            break;
            
        case 'blog':
            // Create as post on platform
            try {
                $blogData = json_decode($item['content'], true);
                $body = $blogData['body'] ?? strip_tags($item['content']);
                $d->query("INSERT INTO posts (user_id, content, type, `status`, created_at) VALUES (2, ?, 'tip', 'active', NOW())", [$body]);
                markPublished($d, $item['id']);
                $result['status'] = 'published_as_post';
            } catch (Throwable $e) {
                markFailed($d, $item['id'], $e->getMessage());
                $result['status'] = 'failed';
            }
            break;
            
        case 'push':
            markPublished($d, $item['id']);
            $result['status'] = 'published';
            break;
            
        default:
            markFailed($d, $item['id'], 'Unknown type');
            $result['status'] = 'failed';
    }
    
    // Log to analytics
    try {
        $d->query("INSERT INTO marketing_analytics (date, channel, impressions, clicks, signups, data) 
            VALUES (CURDATE(), ?, 1, 0, 0, ?) 
            ON DUPLICATE KEY UPDATE impressions = impressions + 1",
            [$item['type'], json_encode($result)]);
    } catch (Throwable $e) {}
    
    return $result;
}

function postToFacebook($pageId, $accessToken, $message) {
    $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'message' => $message,
            'access_token' => $accessToken,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) return ['success' => false, 'error' => 'CURL: ' . $curlError];
    
    $data = json_decode($resp, true);
    if (isset($data['id'])) {
        return ['success' => true, 'post_id' => $data['id']];
    }
    
    $error = $data['error']['message'] ?? 'Unknown error (HTTP ' . $httpCode . ')';
    return ['success' => false, 'error' => $error];
}

function markPublished($d, $id, $platformPostId = '') {
    $d->query("UPDATE content_queue SET `status` = 'published', published_at = NOW(), platform_post_id = ? WHERE id = ?",
        [$platformPostId, $id]);
}

function markFailed($d, $id, $error) {
    $d->query("UPDATE content_queue SET `status` = 'failed', error_log = ? WHERE id = ?", [$error, $id]);
}
