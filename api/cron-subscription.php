<?php
/**
 * ShipperShop — Auto-renew Subscriptions
 * Cron: 0 0 * * * curl https://shippershop.vn/api/cron-subscription.php?key=ss_sub_cron
 * 
 * Checks expiring subscriptions, auto-renews if:
 * - auto_renew = 1
 * - wallet balance >= plan price
 * - expires within 24h
 */
if (($_GET['key'] ?? '') !== 'ss_sub_cron') { http_response_code(403); exit; }
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$d = db();
$renewed = 0;
$failed = 0;
$skipped = 0;

// Find subscriptions expiring within 24 hours with auto_renew=1
$expiring = $d->fetchAll(
    "SELECT us.*, sp.price, sp.name as plan_name, sp.duration_days 
     FROM user_subscriptions us 
     JOIN subscription_plans sp ON us.plan_id = sp.id 
     WHERE us.`status` = 'active' 
     AND us.auto_renew = 1 
     AND us.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 24 HOUR)",
    []
);

foreach ($expiring ?: [] as $sub) {
    $uid = intval($sub['user_id']);
    $price = floatval($sub['price']);
    
    if ($price <= 0) { $skipped++; continue; } // Free plan
    
    // Check wallet balance
    $wallet = $d->fetchOne("SELECT balance FROM wallets WHERE user_id = ?", [$uid]);
    if (!$wallet || floatval($wallet['balance']) < $price) {
        $failed++;
        // Notify user
        try {
            require_once __DIR__ . '/../includes/async-notify.php';
            asyncNotify($uid, 'Gói ' . $sub['plan_name'] . ' sắp hết hạn', 'Số dư không đủ để gia hạn tự động. Vui lòng nạp thêm.', 'wallet', '/wallet.html');
        } catch (Throwable $e) {}
        continue;
    }
    
    // Auto-renew with transaction
    try {
        $pdo = $d->getConnection();
        $pdo->beginTransaction();
        
        // Lock wallet
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$uid]);
        $locked = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$locked || floatval($locked['balance']) < $price) {
            $pdo->rollBack();
            $failed++;
            continue;
        }
        
        $before = floatval($locked['balance']);
        $after = $before - $price;
        
        // Deduct
        $pdo->prepare("UPDATE wallets SET balance = ?, total_spent = total_spent + ?, updated_at = NOW() WHERE user_id = ?")->execute([$after, $price, $uid]);
        
        // Extend subscription
        $pdo->prepare("UPDATE user_subscriptions SET expires_at = DATE_ADD(expires_at, INTERVAL ? DAY), updated_at = NOW() WHERE id = ?")->execute([intval($sub['duration_days']), $sub['id']]);
        
        // Record transaction
        $ref = 'RENEW_' . date('YmdHis') . '_' . $uid;
        $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, description, reference_id, `status`, created_at) VALUES (?, 'payment', ?, ?, ?, ?, ?, 'completed', NOW())")->execute([$uid, $price, $before, $after, 'Gia hạn tự động ' . $sub['plan_name'], $ref]);
        
        $pdo->commit();
        $renewed++;
        
        // Notify
        try {
            require_once __DIR__ . '/../includes/async-notify.php';
            asyncNotify($uid, 'Đã gia hạn ' . $sub['plan_name'], 'Trừ ' . number_format($price) . 'đ. Số dư: ' . number_format($after) . 'đ', 'wallet', '/wallet.html');
        } catch (Throwable $e) {}
        
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        $failed++;
    }
}

echo json_encode([
    'success' => true,
    'renewed' => $renewed,
    'failed' => $failed,
    'skipped' => $skipped,
    'checked' => count($expiring ?: []),
    'timestamp' => date('c')
]);
