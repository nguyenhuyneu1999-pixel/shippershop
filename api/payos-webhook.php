<?php
// payOS Webhook - receives payment confirmation
// URL: https://shippershop.vn/api/payos-webhook.php
// Register this URL at: https://my.payos.vn > Kênh thanh toán > Webhook

define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/payos.php';

header('Content-Type: application/json');

// payOS sends POST with JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log for debugging
$logFile = __DIR__ . '/../uploads/payos_webhook.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " | " . $raw . "\n", FILE_APPEND);

if (!$data || !isset($data['data'])) {
    http_response_code(200); // Always return 200 to payOS
    echo json_encode(['success' => true]); // payOS expects success response
    exit;
}

// Verify signature
if (!payosVerifyWebhook($data)) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | INVALID SIGNATURE\n", FILE_APPEND);
    echo json_encode(['success' => true]);
    exit;
}

$paymentData = $data['data'];
$orderCode = intval($paymentData['orderCode'] ?? 0);
$amount = intval($paymentData['amount'] ?? 0);
$status = $paymentData['code'] ?? '';  // 00 = success
$desc = $paymentData['desc'] ?? '';

file_put_contents($logFile, date('Y-m-d H:i:s') . " | orderCode=$orderCode amount=$amount status=$status\n", FILE_APPEND);

// Only process successful payments
if ($status !== '00' || $orderCode <= 0) {
    echo json_encode(['success' => true]);
    exit;
}

$db = db();

// Find pending payment record
$payment = $db->fetchOne(
    "SELECT * FROM payos_payments WHERE order_code = ? AND `status` = 'pending'",
    [$orderCode]
);

if (!$payment) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | Payment not found for orderCode=$orderCode\n", FILE_APPEND);
    echo json_encode(['success' => true]);
    exit;
}

// Verify amount matches
if (intval($payment['amount']) !== $amount) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | Amount mismatch: expected={$payment['amount']} got=$amount\n", FILE_APPEND);
    echo json_encode(['success' => true]);
    exit;
}

$userId = intval($payment['user_id']);
$planId = intval($payment['plan_id']);
$type = $payment['type']; // 'subscription' or 'deposit'

try {
    $db->beginTransaction();

    // Mark payment as completed
    $db->query("UPDATE payos_payments SET `status` = 'completed', paid_at = NOW(), raw_webhook = ? WHERE id = ?",
        [$raw, $payment['id']]);

    if ($type === 'subscription') {
        // Get plan details
        $plan = $db->fetchOne("SELECT * FROM subscription_plans WHERE id = ?", [$planId]);
        if ($plan) {
            // Deactivate old subscriptions
            $db->query("UPDATE user_subscriptions SET `status` = 'expired' WHERE user_id = ? AND `status` = 'active'", [$userId]);

            // Create new subscription
            $db->query("INSERT INTO user_subscriptions (user_id, plan_id, `status`, started_at, expires_at, created_at) VALUES (?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL ? DAY), NOW())",
                [$userId, $planId, intval($plan['duration_days'])]);

            // Log transaction
            $db->query("INSERT INTO wallet_transactions (user_id, type, amount, `status`, description, created_at) VALUES (?, 'subscription', ?, 'completed', ?, NOW())",
                [$userId, $amount, 'Đăng ký ' . $plan['name'] . ' via payOS']);

            file_put_contents($logFile, date('Y-m-d H:i:s') . " | Subscription activated: user=$userId plan={$plan['name']}\n", FILE_APPEND);
        }
    } elseif ($type === 'deposit') {
        // Add to wallet balance
        $wallet = $db->fetchOne("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
        if ($wallet) {
            $db->query("UPDATE wallets SET balance = balance + ? WHERE user_id = ?", [$amount, $userId]);
        } else {
            $db->query("INSERT INTO wallets (user_id, balance, created_at) VALUES (?, ?, NOW())", [$userId, $amount]);
        }

        // Log transaction
        $db->query("INSERT INTO wallet_transactions (user_id, type, amount, `status`, description, created_at) VALUES (?, 'deposit', ?, 'completed', ?, NOW())",
            [$userId, $amount, 'Nạp tiền via payOS']);

        file_put_contents($logFile, date('Y-m-d H:i:s') . " | Deposit completed: user=$userId amount=$amount\n", FILE_APPEND);
    }

    $db->commit();

    // Send push notification
    try {
        require_once __DIR__ . '/../includes/push-helper.php';
        if ($type === 'subscription') {
            notifyUser($userId, 'Đăng ký thành công!', 'Gói ' . ($plan['name'] ?? '') . ' đã được kích hoạt', 'wallet', '/wallet.html');
        } else {
            notifyUser($userId, 'Nạp tiền thành công!', number_format($amount) . 'đ đã được cộng vào ví', 'wallet', '/wallet.html');
        }
    } catch (Throwable $e) {}

} catch (Throwable $e) {
    $db->rollback();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Always return 200 to payOS
echo json_encode(['success' => true]);
