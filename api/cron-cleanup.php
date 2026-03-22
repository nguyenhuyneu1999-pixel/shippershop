<?php
/**
 * ShipperShop Cleanup Cron
 * Cron: 0 */6 * * * (every 6 hours)
 * URL: /api/cron-cleanup.php?key=ss_cleanup_cron
 */
if (($_GET['key'] ?? '') !== 'ss_cleanup_cron') { http_response_code(403); exit; }
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
$d = db();
$results = [];

// 1. Mark offline users (last_active > 10 min)
try {
    $d->query("UPDATE users SET is_online = 0 WHERE is_online = 1 AND last_active < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $results[] = 'offline_users: OK';
} catch (Throwable $e) { $results[] = 'offline_users: ' . $e->getMessage(); }

// 2. Clean expired CSRF tokens
try {
    $d->query("DELETE FROM csrf_tokens WHERE expires_at < NOW() OR used = 1");
    $results[] = 'csrf_cleanup: OK';
} catch (Throwable $e) { $results[] = 'csrf_cleanup: ' . $e->getMessage(); }

// 3. Clean old rate limit entries
try {
    $d->query("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $results[] = 'rate_limits: OK';
} catch (Throwable $e) { $results[] = 'rate_limits: ' . $e->getMessage(); }

// 4. Clean old login attempts
try {
    $d->query("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $results[] = 'login_attempts: OK';
} catch (Throwable $e) { $results[] = 'login_attempts: ' . $e->getMessage(); }

// 5. Expire old traffic alerts
try {
    $d->query("UPDATE traffic_alerts SET `status` = 'expired' WHERE `status` = 'active' AND expires_at < NOW()");
    $results[] = 'traffic_expire: OK';
} catch (Throwable $e) { $results[] = 'traffic_expire: ' . $e->getMessage(); }

// 6. Clean file cache older than 1 hour
$dir = '/tmp/ss_api_cache';
$cleaned = 0;
if (is_dir($dir)) {
    foreach (glob($dir . '/*.json') as $f) {
        if (filemtime($f) < time() - 3600) { @unlink($f); $cleaned++; }
    }
}
$results[] = "file_cache: cleaned $cleaned files";

echo json_encode(['success' => true, 'results' => $results, 'timestamp' => date('c')]);
