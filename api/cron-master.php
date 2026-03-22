<?php
/**
 * ShipperShop Master Cron — Runs all scheduled tasks
 * Single URL for cPanel cron: /api/cron-master.php?key=ss_master_cron
 * Frequency: every 5 minutes
 */
if (($_GET['key'] ?? '') !== 'ss_master_cron') { http_response_code(403); exit; }
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$results = [];
$start = microtime(true);

// 1. Publish scheduled posts (every run)
try {
    $d = db();
    $drafts = $d->fetchAll("SELECT id FROM posts WHERE is_draft = 1 AND scheduled_at IS NOT NULL AND scheduled_at <= NOW()", []);
    foreach ($drafts ?: [] as $draft) {
        $d->query("UPDATE posts SET is_draft = 0, `status` = 'active' WHERE id = ?", [$draft['id']]);
    }
    $results['publish'] = count($drafts ?: []) . ' posts';
} catch (Throwable $e) { $results['publish'] = 'error: ' . $e->getMessage(); }

// 2. Expire traffic alerts (every run)
try {
    $d->query("UPDATE traffic_alerts SET `status`='expired' WHERE `status`='active' AND expires_at < NOW()");
    $results['traffic_expire'] = 'ok';
} catch (Throwable $e) { $results['traffic_expire'] = 'error'; }

// 3. Mark offline users (every run, >10 min inactive)
try {
    $d->query("UPDATE users SET is_online = 0 WHERE is_online = 1 AND last_active < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
    $results['offline_users'] = 'ok';
} catch (Throwable $e) { $results['offline_users'] = 'error'; }

// 4. Clean expired tokens (every run)
try {
    $d->query("DELETE FROM csrf_tokens WHERE expires_at < NOW() OR used = 1");
    $d->query("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $results['token_cleanup'] = 'ok';
} catch (Throwable $e) { $results['token_cleanup'] = 'error'; }

// 5. Auto-renew subscriptions (check once per hour — use flag)
$hour = date('H');
$minuteBlock = intval(date('i')) < 5; // Only in first 5 min of hour
if ($minuteBlock) {
    try {
        $expiring = $d->fetchAll(
            "SELECT us.id, us.user_id, sp.price, sp.name as plan_name, sp.duration_days
             FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id = sp.id
             WHERE us.`status` = 'active' AND us.auto_renew = 1
             AND us.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)
             AND sp.price > 0", []);
        $renewed = 0;
        foreach ($expiring ?: [] as $sub) {
            $uid = intval($sub['user_id']);
            $price = floatval($sub['price']);
            $wallet = $d->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$uid]);
            if ($wallet && floatval($wallet['balance']) >= $price) {
                $pdo = $d->getConnection();
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
                    $stmt->execute([$uid]);
                    $locked = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($locked && floatval($locked['balance']) >= $price) {
                        $before = floatval($locked['balance']);
                        $after = $before - $price;
                        $pdo->prepare("UPDATE wallets SET balance=?, total_spent=total_spent+?, updated_at=NOW() WHERE user_id=?")->execute([$after, $price, $uid]);
                        $pdo->prepare("UPDATE user_subscriptions SET expires_at=DATE_ADD(expires_at, INTERVAL ? DAY), updated_at=NOW() WHERE id=?")->execute([intval($sub['duration_days']), $sub['id']]);
                        $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,`status`,created_at) VALUES (?,'payment',?,?,?,?,?,'completed',NOW())")->execute([$uid,$price,$before,$after,'Gia hạn '.$sub['plan_name'],'RENEW_'.date('YmdHis').'_'.$uid]);
                        $pdo->commit();
                        $renewed++;
                    } else { $pdo->rollBack(); }
                } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); }
            }
        }
        $results['subscription_renew'] = $renewed . '/' . count($expiring ?: []);
    } catch (Throwable $e) { $results['subscription_renew'] = 'error'; }
} else {
    $results['subscription_renew'] = 'skip (not first 5min of hour)';
}

// 6. Cache cleanup (every run)
$dir = '/tmp/ss_api_cache';
$cleaned = 0;
if (is_dir($dir)) {
    foreach (glob($dir . '/*.json') as $f) {
        if (filemtime($f) < time() - 3600) { @unlink($f); $cleaned++; }
    }
}
$results['cache_cleanup'] = $cleaned . ' files';

// 7. Feed cache flush (only if new content published)
if (intval($results['publish'] ?? 0) > 0) {
    require_once __DIR__ . '/../includes/api-cache.php';
    api_cache_flush('feed_');
    $results['feed_cache'] = 'flushed';
}

$ms = round((microtime(true) - $start) * 1000);
echo json_encode([
    'success' => true,
    'duration_ms' => $ms,
    'tasks' => $results,
    'next_run' => date('Y-m-d H:i:s', time() + 300),
    'timestamp' => date('c')
], JSON_PRETTY_PRINT);
