<?php
// ShipperShop Queue Worker — VPS only
// Usage: php scripts/queue-worker.php
if (php_sapi_name() !== 'cli') die('CLI only');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/queue-adapter.php';

$q = queue();
echo "[Worker] Mode: " . $q->stats()['mode'] . "\n";
if ($q->stats()['mode'] !== 'async') { echo "Redis not found.\n"; exit(0); }

$processed = 0;
$queues = ['notification','optimize_image','update_stats','send_email','audit_log'];
echo "[Worker] Listening...\n";

while (true) {
    $had = false;
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379, 1);
    $redis->select(3);
    foreach ($queues as $qn) {
        $raw = $redis->rPop('ss:queue:' . $qn);
        if (!$raw) continue;
        $job = json_decode($raw, true);
        if (!$job) continue;
        $had = true;
        $ok = $q->processJob($job['type'], $job['data']);
        $processed++;
        echo date('H:i:s') . " $qn = " . ($ok ? 'OK' : 'FAIL') . " [$processed]\n";
    }
    if (!$had) usleep(1000000);
}
