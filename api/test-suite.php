<?php
// ShipperShop API Test Suite — Run all v2 endpoint tests
// Usage: curl https://shippershop.vn/api/test-suite.php?key=ss_test_secret
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (($_GET['key'] ?? '') !== 'ss_test_secret') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid key']);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$d = db();
$results = [];
$passed = 0;
$failed = 0;

function test($name, $condition, $detail = '') {
    global $results, $passed, $failed;
    if ($condition) {
        $passed++;
        $results[] = ['name' => $name, 'status' => 'PASS'];
    } else {
        $failed++;
        $results[] = ['name' => $name, 'status' => 'FAIL', 'detail' => $detail];
    }
}

function apiGet($url) {
    $ch = curl_init('https://shippershop.vn' . $url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'data' => json_decode($body, true)];
}

function apiPost($url, $data = [], $token = null) {
    $ch = curl_init('https://shippershop.vn' . $url);
    $headers = ['Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'data' => json_decode($body, true)];
}

function apiGetAuth($url, $token) {
    $ch = curl_init('https://shippershop.vn' . $url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body, 'data' => json_decode($body, true)];
}

// Generate test tokens
$adminToken = generateJWT(2, 'admin@shippershop.vn', 'admin');
$userToken = generateJWT(3, 'user@shippershop.vn', 'user');

// =============== HEALTH ===============
$r = apiGet('/api/v2/health.php');
test('Health check', $r['code'] === 200 && ($r['data']['db'] ?? '') === 'OK');

// =============== POSTS ===============
$r = apiGet('/api/v2/posts.php?limit=2&sort=hot');
test('Posts: feed', $r['code'] === 200 && $r['data']['success'] && is_array($r['data']['data']['posts']));

$r = apiGet('/api/v2/posts.php?id=42');
test('Posts: single', $r['code'] === 200 && $r['data']['success']);

$r = apiGet('/api/v2/posts.php?action=comments&post_id=42');
test('Posts: comments', $r['code'] === 200 && $r['data']['success']);

$r = apiGet('/api/v2/posts.php?company=GHTK&limit=1');
test('Posts: company filter', $r['code'] === 200 && $r['data']['success']);

$r = apiGet('/api/v2/posts.php?search=giao&limit=1');
test('Posts: search', $r['code'] === 200 && $r['data']['success']);

$r = apiGet('/api/v2/posts.php?sort=trending&limit=1');
test('Posts: trending', $r['code'] === 200 && $r['data']['success']);

$r = apiPost('/api/v2/posts.php?action=vote', ['post_id' => 50], $userToken);
test('Posts: vote', $r['code'] === 200 && $r['data']['success']);

$r = apiPost('/api/v2/posts.php?action=save', ['post_id' => 50], $userToken);
test('Posts: save', $r['code'] === 200 && $r['data']['success']);

$r = apiPost('/api/v2/posts.php?action=vote', ['post_id' => 50]);
test('Posts: vote no auth=401', $r['code'] === 401);

// =============== MESSAGES ===============
$r = apiGetAuth('/api/v2/messages.php?action=conversations', $userToken);
test('Messages: conversations', $r['code'] === 200 && $r['data']['success']);

$r = apiPost('/api/v2/messages.php?action=send', ['to_user_id' => 2, 'content' => 'test suite ' . time()], $userToken);
test('Messages: send', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/messages.php?action=online_friends', $userToken);
test('Messages: online friends', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/messages.php?action=pending_count', $userToken);
test('Messages: pending count', $r['code'] === 200 && isset($r['data']['count']));

$r = apiGetAuth('/api/v2/messages.php?action=group_conversations', $userToken);
test('Messages: group convs', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/messages.php?action=conversations', null);
test('Messages: no auth=401', $r['code'] === 401);

// =============== USERS ===============
$r = apiGetAuth('/api/v2/users.php?action=profile&id=3', $adminToken);
test('Users: profile', $r['code'] === 200 && $r['data']['success'] && !empty($r['data']['data']['fullname']));

$r = apiGetAuth('/api/v2/users.php?action=me', $adminToken);
test('Users: me', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/users.php?action=suggestions&limit=3', $userToken);
test('Users: suggestions', $r['code'] === 200 && $r['data']['success'] && is_array($r['data']['data']));

$r = apiGetAuth('/api/v2/users.php?action=followers&user_id=2', $adminToken);
test('Users: followers', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/users.php?action=settings', $userToken);
test('Users: settings', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/users.php?action=search&q=Nguyen', $userToken);
test('Users: search', $r['code'] === 200 && $r['data']['success']);

// =============== NOTIFICATIONS ===============
$r = apiGetAuth('/api/v2/notifications.php?action=count', $userToken);
test('Notif: count', $r['code'] === 200 && isset($r['data']['count']));

$r = apiGetAuth('/api/v2/notifications.php?action=list', $userToken);
test('Notif: list', $r['code'] === 200 && $r['data']['success']);

// =============== SEARCH ===============
$r = apiGet('/api/v2/search.php?q=shipper');
test('Search: global', $r['code'] === 200 && $r['data']['success']);

$r = apiGet('/api/v2/search.php?action=trending');
test('Search: trending', $r['code'] === 200 && $r['data']['success']);

$r = apiGet('/api/v2/search.php?action=users&q=Nguyen');
test('Search: users', $r['code'] === 200 && $r['data']['success']);

// =============== ADMIN ===============
$r = apiGetAuth('/api/v2/admin.php?action=dashboard', $adminToken);
test('Admin: dashboard', $r['code'] === 200 && $r['data']['success'] && !empty($r['data']['data']['users']));

$r = apiGetAuth('/api/v2/admin.php?action=users&limit=2', $adminToken);
test('Admin: users list', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/admin.php?action=system', $adminToken);
test('Admin: system', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/admin.php?action=analytics&days=7', $adminToken);
test('Admin: analytics', $r['code'] === 200 && $r['data']['success']);

$r = apiGetAuth('/api/v2/admin.php?action=dashboard', $userToken);
test('Admin: non-admin=403', $r['code'] === 403);

// =============== WALLET ===============
$r = apiGet('/api/v2/wallet.php?action=plans');
test('Wallet: plans', $r['code'] === 200 && $r['data']['success'] && count($r['data']['data']) >= 4);

$r = apiGetAuth('/api/v2/wallet.php?action=info', $userToken);
test('Wallet: info', $r['code'] === 200 && $r['data']['success']);

// =============== TRAFFIC ===============
$r = apiGet('/api/v2/traffic.php');
test('Traffic: list', $r['code'] === 200 && $r['data']['success']);

// =============== MARKETPLACE ===============
$r = apiGet('/api/v2/marketplace.php');
test('Marketplace: list', $r['code'] === 200 && $r['data']['success']);

// =============== ANALYTICS ===============
$r = apiPost('/api/v2/analytics.php', ['page' => 'test_suite']);
test('Analytics: pageview', $r['code'] === 200 && $r['data']['success']);

// =============== AUTH ===============
$r = apiPost('/api/auth.php?action=refresh_token', [], $userToken);
test('Auth: refresh token', $r['code'] === 200 && $r['data']['success']);

$r = apiPost('/api/auth.php?action=forgot_password', ['email' => 'test@example.com']);
test('Auth: forgot password', $r['code'] === 200 && $r['data']['success']);

// =============== CRON ===============
$r = apiGet('/api/cron-run.php?key=ss_cron_8f3a2b1c');
test('Cron: runner', $r['code'] === 200 && ($r['data']['cron'] ?? '') === 'OK');

// =============== PAGES ===============
$pages = ['index.html','messages.html','user.html','profile.html','groups.html','group.html',
    'marketplace.html','listing.html','wallet.html','traffic.html','map.html','people.html',
    'post-detail.html','activity-log.html','login.html','register.html','404.html','offline.html'];
foreach ($pages as $page) {
    $r = apiGet('/' . $page);
    test('Page: ' . $page, $r['code'] === 200);
}

// =============== STATIC FILES ===============
$files = ['/css/design-system.css', '/js/core/api.js', '/js/components/post-card.js',
    '/assets/img/defaults/avatar.svg', '/robots.txt', '/sitemap.xml'];
foreach ($files as $file) {
    $r = apiGet($file);
    test('File: ' . $file, $r['code'] === 200);
}

// =============== DB INTEGRITY ===============
// Check posts.likes_count matches actual likes
$mismatches = $d->fetchAll("SELECT p.id, p.likes_count, COUNT(l.id) as real_count FROM posts p LEFT JOIN likes l ON l.post_id = p.id WHERE p.`status`='active' GROUP BY p.id HAVING ABS(p.likes_count - COUNT(l.id)) > 0 LIMIT 5");
test('DB: likes_count integrity', count($mismatches) === 0, count($mismatches) . ' mismatches');

// Check users.total_posts
$badUsers = $d->fetchAll("SELECT u.id, u.total_posts, (SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active') + (SELECT COUNT(*) FROM group_posts WHERE user_id=u.id AND `status`='active') as real FROM users u WHERE u.total_posts > 0 HAVING ABS(u.total_posts - real) > 2 LIMIT 5");
test('DB: total_posts integrity', count($badUsers) <= 2, count($badUsers) . ' off by >2');

// Check table count
$tables = $d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");
test('DB: 69 tables', intval($tables['c']) >= 69, 'has ' . $tables['c']);

// =============== SECURITY ===============
// Check security headers
$ch = curl_init('https://shippershop.vn/index.html');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true, CURLOPT_NOBODY => true, CURLOPT_SSL_VERIFYPEER => false]);
$headers = curl_exec($ch);
curl_close($ch);
test('Security: CSP header', strpos($headers, 'content-security-policy') !== false);
test('Security: HSTS', strpos($headers, 'strict-transport-security') !== false);
test('Security: X-Frame', strpos($headers, 'x-frame-options') !== false);
test('Security: X-Content-Type', strpos($headers, 'x-content-type-options') !== false);

// =============== RESULTS ===============
$total = $passed + $failed;
echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'passed' => $passed,
    'failed' => $failed,
    'total' => $total,
    'score' => $total > 0 ? round($passed / $total * 100, 1) . '%' : '0%',
    'results' => $results
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
