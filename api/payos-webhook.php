<?php
// payOS Webhook - receives payment confirmations
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/payos.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{"success":false}'; exit; }

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!$body) { echo '{"success":true}'; exit; }

$d = db();
$logData = json_encode($body, JSON_UNESCAPED_UNICODE);

// Verify signature
if (!payosVerifyWebhook($body)) {
    try { $d->query("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (0,'payos_invalid',?,?,NOW())", [mb_substr($logData,0,500), $_SERVER['REMOTE_ADDR']??'']); } catch(Throwable $e) {}
    echo '{"success":true}'; exit;
}

$data = $body['data'] ?? [];
$orderCode = intval($data['orderCode'] ?? 0);
$code = $data['code'] ?? '';
$amount = intval($data['amount'] ?? 0);
if (!$orderCode) { echo '{"success":true}'; exit; }

// Find pending payment
$payment = $d->fetchOne("SELECT * FROM payos_payments WHERE order_code=? AND `status`='pending'", [$orderCode]);
if (!$payment) { echo '{"success":true}'; exit; }

$userId = intval($payment['user_id']);
$planId = intval($payment['plan_id']);

if ($code === '00' && $amount >= intval($payment['amount'])) {
    // SUCCESS
    try {
        $pdo = db()->getConnection();
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE payos_payments SET `status`='completed',paid_at=NOW(),payos_data=? WHERE id=?")->execute([$logData,$payment['id']]);
        $planStmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id=?");
        $planStmt->execute([$planId]);
        $plan = $planStmt->fetch(\PDO::FETCH_ASSOC);
        if ($plan) {
            $pdo->prepare("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'")->execute([$userId]);
            $pdo->prepare("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(),INTERVAL ? DAY),0)")->execute([$userId,$planId,intval($plan['duration_days'])]);
            $ref = 'PAYOS_' . $orderCode;
            $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,`status`,created_at) VALUES (?,'payment',?,0,0,?,?,'completed',NOW())")->execute([$userId,intval($payment['amount']),'Thanh toán gói '.$plan['name'].' (QR)',$ref]);
        }
        $pdo->commit();
        try { $d->query("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,'payos_ok',?,?,NOW())", [$userId,'plan='.($plan['name']??$planId).' amt='.$amount,$_SERVER['REMOTE_ADDR']??'']); } catch(Throwable $e) {}
        try { require_once __DIR__.'/../includes/push-helper.php'; notifyUser($userId,'Thanh toán thành công!','Gói '.($plan['name']??'Premium').' đã kích hoạt','wallet','/wallet.html'); } catch(Throwable $e) {}
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    }
} else {
    $d->query("UPDATE payos_payments SET `status`='failed',payos_data=? WHERE id=?", [$logData,$payment['id']]);
}

echo '{"success":true}';
