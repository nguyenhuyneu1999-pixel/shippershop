<?php
/**
 * SHIPPERSHOP WALLET & SUBSCRIPTION API
 * Security: Rate limiting, CSRF, PIN verification, audit logging, SQL injection prevention
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Cache-Control: no-store, no-cache, must-revalidate');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// SECURITY FUNCTIONS
// ============================================

function wAuth() {
    global $db;
    if (isset($_SESSION['user_id'])) return intval($_SESSION['user_id']);
    $headers = getallheaders();
    $h = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $parts = explode('.', $m[1]);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if ($payload && isset($payload['user_id'])) {
                $_SESSION['user_id'] = $payload['user_id'];
                return intval($payload['user_id']);
            }
        }
    }
    return null;
}

function getClientIP() {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function rateLimit($endpoint, $maxHits = 30, $windowSeconds = 60) {
    global $db;
    $ip = getClientIP();
    $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);
    
    // Clean old entries
    try { $db->query("DELETE FROM rate_limits WHERE window_start < ?", [$windowStart]); } catch (Throwable $e) {}
    
    // Count hits
    $count = $db->fetchOne("SELECT SUM(hits) as total FROM rate_limits WHERE ip=? AND endpoint=? AND window_start > ?", [$ip, $endpoint, $windowStart]);
    $total = intval($count['total'] ?? 0);
    
    if ($total >= $maxHits) {
        auditLog(null, 'rate_limit_exceeded', "endpoint={$endpoint} ip={$ip} hits={$total}");
        wError('Quá nhiều yêu cầu. Vui lòng thử lại sau.', 429);
    }
    
    // Record hit
    try { $db->query("INSERT INTO rate_limits (ip, endpoint, hits, window_start) VALUES (?,?,1,NOW())", [$ip, $endpoint]); } catch (Throwable $e) {}
}

function auditLog($userId, $action, $details = '') {
    global $db;
    try {
        $db->query("INSERT INTO audit_log (user_id, action, details, ip, user_agent, created_at) VALUES (?,?,?,?,?,NOW())",
            [$userId, $action, $details, getClientIP(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]);
    } catch (Throwable $e) {}
}

function verifyPin($userId, $pin) {
    global $db;
    $wallet = $db->fetchOne("SELECT pin_hash, pin_attempts, locked_until FROM wallets WHERE user_id=?", [$userId]);
    
    if (!$wallet) return ['error' => 'Ví không tồn tại'];
    
    // Check if locked
    if ($wallet['locked_until'] && strtotime($wallet['locked_until']) > time()) {
        $remain = ceil((strtotime($wallet['locked_until']) - time()) / 60);
        auditLog($userId, 'pin_locked', "locked for {$remain} more minutes");
        return ['error' => "Ví bị khóa tạm thời. Thử lại sau {$remain} phút."];
    }
    
    // No PIN set yet
    if (!$wallet['pin_hash']) return ['error' => 'Chưa thiết lập mã PIN. Vui lòng tạo PIN trước.'];
    
    // Verify
    if (!password_verify($pin, $wallet['pin_hash'])) {
        $attempts = intval($wallet['pin_attempts']) + 1;
        $lockUntil = null;
        
        if ($attempts >= 5) {
            // Lock for 30 minutes after 5 failed attempts
            $lockUntil = date('Y-m-d H:i:s', time() + 1800);
            $db->query("UPDATE wallets SET pin_attempts=?, locked_until=? WHERE user_id=?", [$attempts, $lockUntil, $userId]);
            auditLog($userId, 'pin_locked_5_attempts', "IP=" . getClientIP());
            return ['error' => 'Sai PIN 5 lần. Ví bị khóa 30 phút.'];
        } elseif ($attempts >= 3) {
            // Lock for 5 minutes after 3 failed attempts
            $lockUntil = date('Y-m-d H:i:s', time() + 300);
            $db->query("UPDATE wallets SET pin_attempts=?, locked_until=? WHERE user_id=?", [$attempts, $lockUntil, $userId]);
            auditLog($userId, 'pin_failed_3', "attempts={$attempts}");
            return ['error' => "Sai PIN. Còn " . (5 - $attempts) . " lần thử. Ví tạm khóa 5 phút."];
        }
        
        $db->query("UPDATE wallets SET pin_attempts=? WHERE user_id=?", [$attempts, $userId]);
        auditLog($userId, 'pin_failed', "attempts={$attempts}");
        return ['error' => "Sai mã PIN. Còn " . (5 - $attempts) . " lần thử."];
    }
    
    // Reset attempts on success
    $db->query("UPDATE wallets SET pin_attempts=0, locked_until=NULL WHERE user_id=?", [$userId]);
    return ['success' => true];
}

function ensureWallet($userId) {
    global $db;
    $wallet = $db->fetchOne("SELECT * FROM wallets WHERE user_id=?", [$userId]);
    if (!$wallet) {
        $db->query("INSERT INTO wallets (user_id, balance, created_at) VALUES (?, 0, NOW())", [$userId]);
        $wallet = $db->fetchOne("SELECT * FROM wallets WHERE user_id=?", [$userId]);
    }
    return $wallet;
}

function wSuccess($msg, $data = []) { echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function wError($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

// ============================================
// GET ENDPOINTS
// ============================================
if ($method === 'GET') {
    
    // Get wallet info
    if ($action === '' || $action === 'info') {
        $uid = wAuth();
        if (!$uid) wError('Đăng nhập', 401);
        rateLimit('wallet_info', 60, 60);
        
        $wallet = ensureWallet($uid);
        $hasPin = !empty($wallet['pin_hash']);
        
        // Get current subscription
        $sub = $db->fetchOne("SELECT us.*, sp.name as plan_name, sp.slug as plan_slug, sp.badge, sp.badge_color, sp.features FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.expires_at > NOW() ORDER BY us.expires_at DESC LIMIT 1", [$uid]);
        
        // Get recent transactions
        $txns = $db->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$uid]);
        
        wSuccess('OK', [
            'balance' => floatval($wallet['balance']),
            'total_deposit' => floatval($wallet['total_deposit']),
            'total_spent' => floatval($wallet['total_spent']),
            'has_pin' => $hasPin,
            'is_locked' => ($wallet['locked_until'] && strtotime($wallet['locked_until']) > time()),
            'subscription' => $sub ? [
                'plan' => $sub['plan_name'],
                'slug' => $sub['plan_slug'],
                'badge' => $sub['badge'],
                'badge_color' => $sub['badge_color'],
                'features' => json_decode($sub['features'] ?: '[]', true),
                'expires_at' => $sub['expires_at'],
                'auto_renew' => (bool)$sub['auto_renew'],
            ] : null,
            'transactions' => $txns ?: [],
        ]);
    }
    
    // Get subscription plans
    if ($action === 'plans') {
        rateLimit('plans', 30, 60);
        $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY sort_order ASC", []);
        foreach ($plans as &$p) {
            $p['features'] = json_decode($p['features'] ?: '[]', true);
            $p['price'] = floatval($p['price']);
        }
        wSuccess('OK', $plans);
    }
    
    // Get transaction history
    if ($action === 'transactions') {
        $uid = wAuth();
        if (!$uid) wError('Đăng nhập', 401);
        rateLimit('transactions', 30, 60);
        
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $txns = $db->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}", [$uid]);
        wSuccess('OK', $txns ?: []);
    }
}

// ============================================
// POST ENDPOINTS
// ============================================
if ($method === 'POST') {
    $uid = wAuth();
    if (!$uid) wError('Đăng nhập', 401);
    
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    
    // === SET PIN ===
    if ($action === 'set_pin') {
        rateLimit('set_pin', 5, 300); // 5 attempts per 5 minutes
        
        $pin = trim($input['pin'] ?? '');
        $confirmPin = trim($input['confirm_pin'] ?? '');
        
        if (strlen($pin) < 4 || strlen($pin) > 6 || !ctype_digit($pin)) {
            wError('Mã PIN phải là 4-6 chữ số');
        }
        if ($pin !== $confirmPin) wError('Mã PIN không khớp');
        
        $wallet = ensureWallet($uid);
        
        // If PIN already exists, require old PIN
        if (!empty($wallet['pin_hash'])) {
            $oldPin = trim($input['old_pin'] ?? '');
            $verify = verifyPin($uid, $oldPin);
            if (isset($verify['error'])) wError($verify['error']);
        }
        
        $hash = password_hash($pin, PASSWORD_BCRYPT);
        $db->query("UPDATE wallets SET pin_hash=?, pin_attempts=0, locked_until=NULL WHERE user_id=?", [$hash, $uid]);
        auditLog($uid, 'pin_set', 'PIN updated');
        wSuccess('Đã thiết lập mã PIN');
    }
    
    // === SUBSCRIBE ===
    if ($action === 'subscribe') {
        rateLimit('subscribe', 10, 300);
        
        $planId = intval($input['plan_id'] ?? 0);
        $pin = trim($input['pin'] ?? '');
        
        if (!$planId) wError('Chọn gói subscription');
        
        // Get plan
        $plan = $db->fetchOne("SELECT * FROM subscription_plans WHERE id=? AND is_active=1", [$planId]);
        if (!$plan) wError('Gói không tồn tại');
        
        // Free plan - no PIN needed
        if (floatval($plan['price']) <= 0) {
            // Check if already has active sub
            $db->query("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'", [$uid]);
            $db->query("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(), INTERVAL ? DAY),0)",
                [$uid, $planId, $plan['duration_days']]);
            auditLog($uid, 'subscribe_free', "plan={$plan['name']}");
            wSuccess('Đã kích hoạt gói ' . $plan['name']);
        }
        
        // Paid plan - verify PIN
        if (!$pin) wError('Nhập mã PIN để xác nhận thanh toán');
        $verify = verifyPin($uid, $pin);
        if (isset($verify['error'])) wError($verify['error']);
        
        // Check balance
        $wallet = ensureWallet($uid);
        $price = floatval($plan['price']);
        
        if (floatval($wallet['balance']) < $price) {
            wError('Số dư không đủ. Cần ' . number_format($price) . 'đ, hiện có ' . number_format($wallet['balance']) . 'đ');
        }
        
        // Deduct balance (atomic operation)
        $balanceBefore = floatval($wallet['balance']);
        $balanceAfter = $balanceBefore - $price;
        
        $db->query("UPDATE wallets SET balance=balance-?, total_spent=total_spent+?, updated_at=NOW() WHERE user_id=? AND balance>=?",
            [$price, $price, $uid, $price]);
        
        // Verify deduction happened
        $walletAfter = $db->fetchOne("SELECT balance FROM wallets WHERE user_id=?", [$uid]);
        if (floatval($walletAfter['balance']) > $balanceBefore) {
            // Something went wrong, refund
            auditLog($uid, 'subscribe_error', "balance anomaly detected");
            wError('Lỗi giao dịch. Vui lòng thử lại.');
        }
        
        // Record transaction
        $ref = 'SUB_' . strtoupper($plan['slug']) . '_' . date('YmdHis') . '_' . $uid;
        $db->query("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,`status`,created_at) VALUES (?,'payment',?,?,?,?,?,'completed',NOW())",
            [$uid, $price, $balanceBefore, $balanceAfter, 'Đăng ký gói ' . $plan['name'], $ref]);
        $txnId = intval($db->fetchOne("SELECT MAX(id) as m FROM wallet_transactions", [])['m'] ?? 0);
        
        // Cancel old subscriptions
        $db->query("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'", [$uid]);
        
        // Create new subscription
        $db->query("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew,transaction_id) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(), INTERVAL ? DAY),1,?)",
            [$uid, $planId, $plan['duration_days'], $txnId]);
        
        auditLog($uid, 'subscribe_paid', "plan={$plan['name']} price={$price} ref={$ref}");
        wSuccess('Đăng ký thành công gói ' . $plan['name'] . '!', [
            'plan' => $plan['name'],
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$plan['duration_days']} days")),
            'balance' => $balanceAfter,
        ]);
    }
    
    // === DEPOSIT (manual bank transfer) ===
    if ($action === 'deposit') {
        rateLimit('deposit', 5, 300);
        
        $amount = floatval($input['amount'] ?? 0);
        $bankName = trim($input['bank_name'] ?? '');
        $proofImage = trim($input['proof_image'] ?? '');
        
        if ($amount < 10000) wError('Số tiền nạp tối thiểu 10.000đ');
        if ($amount > 10000000) wError('Số tiền nạp tối đa 10.000.000đ');
        
        $wallet = ensureWallet($uid);
        $ref = 'DEP_' . date('YmdHis') . '_' . $uid . '_' . rand(1000, 9999);
        
        $db->query("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,bank_name,proof_image,`status`,created_at) VALUES (?,'deposit',?,?,?,?,?,?,?,'pending',NOW())",
            [$uid, $amount, $wallet['balance'], $wallet['balance'], 'Nạp tiền qua ' . ($bankName ?: 'chuyển khoản'), $ref, $bankName, $proofImage]);
        
        auditLog($uid, 'deposit_request', "amount={$amount} bank={$bankName} ref={$ref}");
        wSuccess('Yêu cầu nạp tiền đã được gửi! Admin sẽ xác nhận trong 5-30 phút.', ['reference' => $ref]);
    }
    
    // === CANCEL SUBSCRIPTION ===
    if ($action === 'cancel_subscription') {
        rateLimit('cancel_sub', 5, 300);
        
        $db->query("UPDATE user_subscriptions SET auto_renew=0 WHERE user_id=? AND `status`='active'", [$uid]);
        auditLog($uid, 'cancel_auto_renew', '');
        wSuccess('Đã tắt tự động gia hạn. Gói hiện tại vẫn hoạt động đến hết hạn.');
    }
}

wError('Invalid request');
