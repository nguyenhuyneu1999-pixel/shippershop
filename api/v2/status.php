<?php
// ShipperShop Production Status — public health dashboard
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/cache.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=60');

$start = microtime(true);
$d = db();
$checks = [];

// DB connection
try {
    $d->fetchOne("SELECT 1");
    $checks['database'] = ['status' => 'ok', 'tables' => intval($d->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()")['c'])];
} catch (\Throwable $e) {
    $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Key tables writable
try {
    $d->query("INSERT INTO page_views (page,ip,created_at) VALUES ('_health_check','127.0.0.1',NOW())");
    $d->query("DELETE FROM page_views WHERE page='_health_check'");
    $checks['db_write'] = ['status' => 'ok'];
} catch (\Throwable $e) {
    $checks['db_write'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Cache
try {
    cache_set('_health', time(), 10);
    $v = cache_get('_health');
    cache_del('_health');
    $checks['cache'] = ['status' => $v ? 'ok' : 'error'];
} catch (\Throwable $e) {
    $checks['cache'] = ['status' => 'error'];
}

// Disk space
$free = disk_free_space('/');
$total = disk_total_space('/');
$usedPct = round((1 - $free / $total) * 100, 1);
$checks['disk'] = ['status' => $usedPct < 90 ? 'ok' : 'warning', 'used_pct' => $usedPct, 'free_mb' => round($free / 1048576)];

// Upload dir writable
$checks['uploads'] = ['status' => is_writable(__DIR__ . '/../../uploads') ? 'ok' : 'error'];

// Key files exist
$keyFiles = ['index.html', 'css/design-system.css', 'js/ss-bundle.min.js', 'sw.js', '.htaccess'];
$missing = [];
foreach ($keyFiles as $f) {
    if (!file_exists(__DIR__ . '/../../' . $f)) $missing[] = $f;
}
$checks['key_files'] = ['status' => empty($missing) ? 'ok' : 'warning', 'missing' => $missing];

// PHP version
$checks['php'] = ['status' => version_compare(PHP_VERSION, '8.0', '>=') ? 'ok' : 'warning', 'version' => PHP_VERSION];

// Response time
$ms = round((microtime(true) - $start) * 1000, 2);

// Recent errors (last hour)
try {
    $errorCount = intval($d->fetchOne("SELECT COUNT(*) as c FROM error_logs WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c']);
    $checks['recent_errors'] = ['status' => $errorCount < 10 ? 'ok' : 'warning', 'count' => $errorCount];
} catch (\Throwable $e) {
    $checks['recent_errors'] = ['status' => 'unknown'];
}

// Overall
$allOk = true;
foreach ($checks as $c) {
    if ($c['status'] !== 'ok') { $allOk = false; break; }
}

echo json_encode([
    'status' => $allOk ? 'healthy' : 'degraded',
    'timestamp' => date('c'),
    'response_ms' => $ms,
    'checks' => $checks,
    'version' => '2.0.0'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
