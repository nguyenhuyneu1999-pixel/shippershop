<?php
// ShipperShop Cron Runner
// Setup: cPanel > Cron Jobs > every 5 min: php /home/nhshiw2j/public_html/includes/cron/runner.php
// Or: curl https://shippershop.vn/api/cron-run.php?key=CRON_SECRET
define('CRON_SECRET', 'ss_cron_8f3a2b1c');
$isWeb = php_sapi_name() !== 'cli';

if ($isWeb) {
    header('Content-Type: application/json');
    if (($_GET['key'] ?? '') !== CRON_SECRET) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid key']);
        exit;
    }
}

try {

// Find includes path
$basePath = __DIR__ . '/../';
if (!file_exists($basePath . 'config.php')) {
    $basePath = __DIR__ . '/../../includes/';
}
require_once $basePath . 'config.php';
require_once $basePath . 'db.php';

$d = db();
$results = [];

// ===== JOB 1: Cleanup =====
$start = microtime(true);
try {
    // Delete old rate limits (> 1 hour)
    $d->query("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    // Delete old login attempts (> 24 hours)
    $d->query("DELETE FROM login_attempts WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    // Delete old error logs (> 30 days)
    $d->query("DELETE FROM error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    // Delete old page views (> 90 days)
    $d->query("DELETE FROM page_views WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    // Delete old search history (> 30 days)
    $d->query("DELETE FROM search_history WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    // Delete old cron logs (> 7 days)
    $d->query("DELETE FROM cron_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    // Clean file cache
    $cacheDir = '/tmp/ss_cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.cache');
        $cleaned = 0;
        foreach ($files as $f) {
            if (filemtime($f) < time() - 3600) { @unlink($f); $cleaned++; }
        }
    }
    $ms = round((microtime(true) - $start) * 1000);
    $d->query("INSERT INTO cron_logs (job_name, `status`, duration_ms, message, created_at) VALUES ('cleanup', 'success', ?, 'OK', NOW())", [$ms]);
    $results['cleanup'] = ['status' => 'OK', 'ms' => $ms];
} catch (\Throwable $e) {
    $d->query("INSERT INTO cron_logs (job_name, `status`, duration_ms, message, created_at) VALUES ('cleanup', 'failed', 0, ?, NOW())", [$e->getMessage()]);
    $results['cleanup'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
}

// ===== JOB 2: Sync denormalized counts =====
$start = microtime(true);
try {
    // Sync users.total_success + total_posts (only users active in last 24h)
    $d->query("UPDATE users u SET
        total_success = (
            COALESCE((SELECT SUM(likes_count) FROM posts WHERE user_id=u.id AND `status`='active'),0) +
            COALESCE((SELECT SUM(likes_count) FROM group_posts WHERE user_id=u.id AND `status`='active'),0)
        ),
        total_posts = (
            COALESCE((SELECT COUNT(*) FROM posts WHERE user_id=u.id AND `status`='active'),0) +
            COALESCE((SELECT COUNT(*) FROM group_posts WHERE user_id=u.id AND `status`='active'),0)
        )
        WHERE u.last_active > DATE_SUB(NOW(), INTERVAL 24 HOUR) OR u.last_login > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    // Sync posts.comments_count
    $d->query("UPDATE posts p SET comments_count = (SELECT COUNT(*) FROM comments WHERE post_id=p.id AND `status`='active') WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $ms = round((microtime(true) - $start) * 1000);
    $d->query("INSERT INTO cron_logs (job_name, `status`, duration_ms, message, created_at) VALUES ('sync_counts', 'success', ?, 'OK', NOW())", [$ms]);
    $results['sync_counts'] = ['status' => 'OK', 'ms' => $ms];
} catch (\Throwable $e) {
    $results['sync_counts'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
}

// ===== JOB 3: Auto-publish content queue =====
$start = microtime(true);
try {
    $items = $d->fetchAll("SELECT * FROM content_queue WHERE `status`='pending' AND scheduled_at <= NOW() ORDER BY scheduled_at ASC LIMIT 10");
    $published = 0;
    $pdo = $d->getConnection();
    foreach ($items as $item) {
        try {
            $ins = $pdo->prepare("INSERT INTO posts (user_id, content, type, province, district, ward, `status`, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $ins->execute([
                $item['user_id'], $item['content'], $item['type'] ?? 'post',
                $item['province'] ?? '', $item['district'] ?? '', $item['ward'] ?? ''
            ]);
            $d->query("UPDATE content_queue SET `status`='published' WHERE id=?", [$item['id']]);
            $published++;
        } catch (\Throwable $e) {
            $d->query("UPDATE content_queue SET `status`='failed' WHERE id=?", [$item['id']]);
        }
    }
    $ms = round((microtime(true) - $start) * 1000);
    $d->query("INSERT INTO cron_logs (job_name, `status`, duration_ms, message, created_at) VALUES ('auto_publish', 'success', ?, ?, NOW())", [$ms, 'Published: ' . $published]);
    $results['auto_publish'] = ['status' => 'OK', 'ms' => $ms, 'published' => $published];
} catch (\Throwable $e) {
    $results['auto_publish'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
}

// ===== JOB 4: Mark offline users =====
$start = microtime(true);
try {
    $d->query("UPDATE users SET is_online=0 WHERE is_online=1 AND last_active < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $ms = round((microtime(true) - $start) * 1000);
    $results['mark_offline'] = ['status' => 'OK', 'ms' => $ms];
} catch (\Throwable $e) {
    $results['mark_offline'] = ['status' => 'FAIL'];
}

// ===== JOB 5: Publish scheduled posts =====
$start = microtime(true);
try {
    $scheduled = $d->fetchAll("SELECT id FROM posts WHERE scheduled_at IS NOT NULL AND scheduled_at <= NOW() AND is_draft=0 AND `status`='active'");
    $pubCount = 0;
    foreach ($scheduled as $sp) {
        $d->query("UPDATE posts SET scheduled_at=NULL, created_at=NOW() WHERE id=?", [$sp['id']]);
        $pubCount++;
    }
    $ms = round((microtime(true) - $start) * 1000);
    $d->query("INSERT INTO cron_logs (job_name, `status`, duration_ms, message, created_at) VALUES ('publish_scheduled', 'success', ?, ?, NOW())", [$ms, 'Published: ' . $pubCount]);
    $results['publish_scheduled'] = ['status' => 'OK', 'ms' => $ms, 'published' => $pubCount];
} catch (\Throwable $e) {
    $results['publish_scheduled'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
}

// ===== JOB 6: Clean expired stories =====
$start = microtime(true);
try {
    $expired = $d->fetchOne("SELECT COUNT(*) as c FROM stories WHERE expires_at < NOW()");
    $d->query("DELETE FROM story_views WHERE story_id IN (SELECT id FROM stories WHERE expires_at < NOW())");
    $d->query("DELETE FROM stories WHERE expires_at < NOW()");
    $ms = round((microtime(true) - $start) * 1000);
    $results['clean_stories'] = ['status' => 'OK', 'ms' => $ms, 'removed' => intval($expired['c'] ?? 0)];
} catch (\Throwable $e) {
    $results['clean_stories'] = ['status' => 'FAIL'];
}

// ===== JOB 7: Auto-renew subscriptions =====
$start = microtime(true);
try {
    // Find expired subscriptions with auto_renew=1
    $expired = $d->fetchAll("SELECT us.*, sp.price, sp.name as plan_name FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.end_date < NOW() AND us.auto_renew=1 AND us.`status`='active'");
    $renewed = 0;
    foreach ($expired as $sub) {
        $userId = intval($sub['user_id']);
        $price = intval($sub['price']);
        $wallet = $d->fetchOne("SELECT balance FROM wallets WHERE user_id=?", [$userId]);
        if ($wallet && intval($wallet['balance']) >= $price) {
            // Deduct + extend
            $d->query("UPDATE wallets SET balance=balance-? WHERE user_id=?", [$price, $userId]);
            $d->query("UPDATE user_subscriptions SET end_date=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id=?", [$sub['id']]);
            $pdo = $d->getConnection();
            $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description,created_at) VALUES (?,'subscription',?,?,NOW())")->execute([$userId, -$price, 'Auto-renew: ' . $sub['plan_name']]);
            try { $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'wallet','Gia hạn tự động',?,?,NOW())")->execute([$userId, 'Gói ' . $sub['plan_name'] . ' đã được gia hạn', json_encode(['plan_id' => $sub['plan_id']])]); } catch (\Throwable $e) {}
            $renewed++;
        } else {
            // Insufficient balance — cancel
            $d->query("UPDATE user_subscriptions SET `status`='expired',auto_renew=0 WHERE id=?", [$sub['id']]);
            try { $pdo = $d->getConnection(); $pdo->prepare("INSERT INTO notifications (user_id,type,title,message,data,created_at) VALUES (?,'wallet','Gói hết hạn',?,?,NOW())")->execute([$userId, 'Gói ' . $sub['plan_name'] . ' đã hết hạn do không đủ số dư', '{}']); } catch (\Throwable $e) {}
        }
    }
    $ms = round((microtime(true) - $start) * 1000);
    $results['auto_renew'] = ['status' => 'OK', 'ms' => $ms, 'renewed' => $renewed, 'expired' => count($expired)];
} catch (\Throwable $e) {
    $results['auto_renew'] = ['status' => 'FAIL', 'error' => $e->getMessage()];
}

// ===== JOB 8: Clean stale typing indicators + old analytics =====
$start = microtime(true);
try {
    $d->query("DELETE FROM typing_indicators WHERE started_at < DATE_SUB(NOW(), INTERVAL 30 SECOND)");
    // Clean analytics older than 90 days
    $d->query("DELETE FROM analytics_views WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $ms = round((microtime(true) - $start) * 1000);
    $results['clean_stale'] = ['status' => 'OK', 'ms' => $ms];
} catch (\Throwable $e) {
    $results['clean_stale'] = ['status' => 'FAIL'];
}

// Output
if ($isWeb) {
    echo json_encode(['cron' => 'OK', 'timestamp' => date('Y-m-d H:i:s'), 'jobs' => $results], JSON_PRETTY_PRINT);
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Cron complete: " . json_encode($results) . "\n";
}

} catch (\Throwable $e) {
    echo json_encode(['cron' => 'ERROR', 'message' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
}
