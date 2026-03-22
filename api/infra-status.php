<?php
/**
 * ShipperShop Infrastructure Status
 * Shows all module status: cache, queue, DB, storage, realtime
 * URL: /api/infra-status.php?key=ss_infra_key
 */
if (($_GET['key'] ?? '') !== 'ss_infra_key') { http_response_code(403); exit; }

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$result = ['modules' => [], 'environment' => 'shared_hosting'];

// 1. Smart Cache
if (file_exists(__DIR__ . '/../includes/smart-cache.php')) {
    require_once __DIR__ . '/../includes/smart-cache.php';
    $c = scache();
    $result['modules']['cache'] = ['status' => 'ok', 'mode' => $c->isRedis() ? 'redis' : 'file'];
    if ($c->isRedis()) $result['environment'] = 'vps';
}

// 2. Queue
if (file_exists(__DIR__ . '/../includes/queue-adapter.php')) {
    require_once __DIR__ . '/../includes/queue-adapter.php';
    $q = queue();
    $stats = $q->stats();
    $result['modules']['queue'] = ['status' => 'ok', 'mode' => $stats['mode'], 'queues' => $stats['queues'] ?? []];
}

// 3. DB Router
if (file_exists(__DIR__ . '/../includes/db-router.php')) {
    require_once __DIR__ . '/../includes/db-router.php';
    $result['modules']['db_router'] = ['status' => 'ok', 'mode' => dbRouter()->info()['mode']];
    if (dbRouter()->info()['has_replica']) $result['environment'] = 'vps_scaled';
}

// 4. Storage
if (file_exists(__DIR__ . '/../includes/storage-adapter.php')) {
    require_once __DIR__ . '/../includes/storage-adapter.php';
    $result['modules']['storage'] = ['status' => 'ok', 'mode' => storage()->info()['mode']];
}

// 5. Realtime
if (file_exists(__DIR__ . '/../includes/realtime-adapter.php')) {
    require_once __DIR__ . '/../includes/realtime-adapter.php';
    $result['modules']['realtime'] = ['status' => 'ok', 'mode' => realtime()->info()['mode']];
}

// 6. DB connection
try {
    $t = microtime(true);
    db()->fetchOne("SELECT 1");
    $ms = round((microtime(true) - $t) * 1000, 1);
    $result['modules']['database'] = ['status' => 'ok', 'latency_ms' => $ms];
} catch (Throwable $e) {
    $result['modules']['database'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// 7. Capacity estimate
$modes = array_column($result['modules'], 'mode');
if (in_array('redis', $modes) && in_array('primary+replica', $modes)) {
    $result['estimated_capacity'] = '50K-100K concurrent';
} elseif (in_array('redis', $modes)) {
    $result['estimated_capacity'] = '5K-30K concurrent';
} else {
    $result['estimated_capacity'] = '200-2K concurrent (add Redis for more)';
}

$result['timestamp'] = date('c');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
