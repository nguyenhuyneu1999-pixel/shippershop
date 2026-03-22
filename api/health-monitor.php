<?php
/**
 * ShipperShop Health Monitor — Auto-recovery system
 * Chạy mỗi 1 phút, tự động khắc phục sự cố
 * URL: /api/health-monitor.php?key=ss_health_key
 */
if (($_GET['key'] ?? '') !== 'ss_health_key') {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json');
$start = microtime(true);
$checks = [];
$actions = [];

// 1. Check DB connection
try {
    require_once __DIR__ . '/../includes/db.php';
    $d = db();
    $t = microtime(true);
    $d->fetchOne("SELECT 1");
    $dbMs = round((microtime(true) - $t) * 1000, 1);
    $checks[] = ['name' => 'database', 'status' => $dbMs < 500 ? 'ok' : 'slow', 'ms' => $dbMs];
} catch (Throwable $e) {
    $checks[] = ['name' => 'database', 'status' => 'down', 'error' => $e->getMessage()];
}

// 2. Check disk space
$diskFree = disk_free_space('/');
$diskTotal = disk_total_space('/');
$diskPct = $diskTotal > 0 ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0;
$checks[] = ['name' => 'disk', 'status' => $diskPct < 85 ? 'ok' : 'warning', 'used_pct' => $diskPct];

// 3. Check cache directory size
$cacheSize = 0;
$cacheFiles = glob('/tmp/ss_api_cache/*.json');
$cacheCount = count($cacheFiles ?: []);
foreach ($cacheFiles ?: [] as $f) $cacheSize += filesize($f);
$checks[] = ['name' => 'cache', 'status' => 'ok', 'files' => $cacheCount, 'size_kb' => round($cacheSize / 1024, 1)];

// AUTO-RECOVERY: Clear old cache if > 1000 files
if ($cacheCount > 1000) {
    $cleaned = 0;
    foreach ($cacheFiles as $f) {
        $data = @json_decode(@file_get_contents($f), true);
        if (!$data || !isset($data['exp']) || $data['exp'] < time()) {
            @unlink($f);
            $cleaned++;
        }
    }
    $actions[] = "Cleaned $cleaned expired cache files";
}

// 4. Check PHP memory
$memUsed = round(memory_get_usage(true) / 1048576, 1);
$checks[] = ['name' => 'memory', 'status' => $memUsed < 64 ? 'ok' : 'warning', 'used_mb' => $memUsed];

// 5. Check recent errors (last hour)
try {
    $errorCount = intval($d->fetchOne("SELECT COUNT(*) as c FROM audit_log WHERE action LIKE '%error%' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)")['c'] ?? 0);
    $checks[] = ['name' => 'errors_1h', 'status' => $errorCount < 10 ? 'ok' : 'warning', 'count' => $errorCount];
} catch (Throwable $e) {
    $checks[] = ['name' => 'errors_1h', 'status' => 'unknown'];
}

// 6. Check API response time
$t = microtime(true);
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$resp = @file_get_contents('https://shippershop.vn/api/v2/status.php', false, $ctx);
$apiMs = round((microtime(true) - $t) * 1000);
$checks[] = ['name' => 'api_latency', 'status' => $apiMs < 2000 ? 'ok' : 'slow', 'ms' => $apiMs];

// Overall status
$okCount = count(array_filter($checks, function($c) { return $c['status'] === 'ok'; }));
$overall = $okCount === count($checks) ? 'healthy' : ($okCount >= count($checks) - 1 ? 'degraded' : 'unhealthy');

$totalMs = round((microtime(true) - $start) * 1000);

echo json_encode([
    'status' => $overall,
    'checks' => $checks,
    'actions' => $actions,
    'ok' => $okCount,
    'total' => count($checks),
    'uptime_check_ms' => $totalMs,
    'timestamp' => date('c')
], JSON_UNESCAPED_UNICODE);
