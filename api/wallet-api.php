<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================
// AUTO MIGRATION - tạo bảng còn thiếu
// ============================================
try {
    $pdo = db()->getConnection();

    $pdo->exec("CREATE TABLE IF NOT EXISTS `subscription_plans` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(50) NOT NULL,
        `price` decimal(15,2) NOT NULL DEFAULT 0,
        `duration_days` int(11) NOT NULL DEFAULT 30,
        `post_limit` int(11) NOT NULL DEFAULT 3,
        `badge` varchar(20) DEFAULT NULL,
        `badge_color` varchar(20) DEFAULT NULL,
        `features` text DEFAULT NULL,
        `is_active` tinyint(1) NOT NULL DEFAULT 1,
        `sort_order` int(11) NOT NULL DEFAULT 0,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("INSERT IGNORE INTO `subscription_plans` (`id`,`name`,`slug`,`price`,`duration_days`,`post_limit`,`badge`,`badge_color`,`features`,`is_active`,`sort_order`) VALUES
        (1,'Miễn phí','free',0,99999,3,NULL,NULL,'[\"3 bài/ngày\",\"Cộng đồng shipper\",\"Cảnh báo giao thông\"]',1,1),
        (2,'Shipper Pro','pro',49000,30,20,'⭐ PRO','#f59e0b','[\"20 bài/ngày\",\"Badge PRO\",\"Ưu tiên hiển thị\"]',1,2),
        (3,'Shipper VIP','vip',99000,30,99999,'👑 VIP','#8b5cf6','[\"Không giới hạn bài\",\"Badge VIP\",\"Ưu tiên cao\"]',1,3),
        (4,'Shipper Premium','premium',199000,30,99999,'💎 PREMIUM','#EE4D2D','[\"Không giới hạn\",\"Badge PREMIUM\",\"Ưu tiên tối đa\",\"Hỗ trợ riêng\"]',1,4)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_subscriptions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `plan_id` int(11) NOT NULL,
        `status` varchar(20) NOT NULL DEFAULT 'active',
        `started_at` datetime DEFAULT CURRENT_TIMESTAMP,
        `expires_at` datetime DEFAULT NULL,
        `auto_renew` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `plan_id` (`plan_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `csrf_tokens` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `token` varchar(64) NOT NULL,
        `expires_at` datetime NOT NULL,
        `used` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `token` (`token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `rate_limits` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ip` varchar(45) NOT NULL,
        `endpoint` varchar(100) NOT NULL,
        `hits` int(11) NOT NULL DEFAULT 1,
        `window_start` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `ip_endpoint` (`ip`,`endpoint`),
        KEY `window_start` (`window_start`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `audit_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `action` varchar(100) NOT NULL,
        `details` text DEFAULT NULL,
        `ip` varchar(45) DEFAULT NULL,
        `user_agent` varchar(500) DEFAULT NULL,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `action` (`action`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Thêm cột còn thiếu vào wallets nếu chưa có
    $walletCols = array_column($pdo->query("SHOW COLUMNS FROM wallets")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('pin_hash', $walletCols)) {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN `pin_hash` varchar(255) DEFAULT NULL");
    }
    if (!in_array('pin_attempts', $walletCols)) {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN `pin_attempts` int(11) NOT NULL DEFAULT 0");
    }
    if (!in_array('locked_until', $walletCols)) {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN `locked_until` datetime DEFAULT NULL");
    }
    if (!in_array('total_deposit', $walletCols)) {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN `total_deposit` decimal(15,2) NOT NULL DEFAULT 0");
    }
    if (!in_array('total_spent', $walletCols)) {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN `total_spent` decimal(15,2) NOT NULL DEFAULT 0");
    }
    if (!in_array('created_at', $walletCols)) {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN `created_at` datetime DEFAULT CURRENT_TIMESTAMP");
    }

} catch (Throwable $e) {
    error_log("Wallet migration error: " . $e->getMessage());
}

// ============================================
// SECURITY LAYER
// ============================================

/**
 * FIX #1: JWT Authentication with SIGNATURE VERIFICATION
 * Before: just base64_decode payload (anyone can forge)
 * After: verify HMAC-SHA256 signature against JWT_SECRET
 */
function wAuth() {
    // Session first (most secure - server-side)
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return intval($_SESSION['user_id']);
    }
    
    $headers = getallheaders();
    $h = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.+)/i', $h, $m)) return null;
    
    $token = $m[1];
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    
    // VERIFY SIGNATURE - This is the critical fix
    $header = $parts[0];
    $payload = $parts[1];
    $signature = $parts[2];
    
    $validSig = base64_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    // URL-safe base64 comparison
    $validSig = rtrim(strtr($validSig, '+/', '-_'), '=');
    $testSig = rtrim(strtr($signature, '+/', '-_'), '=');
    
    if (!hash_equals($validSig, $testSig)) {
        // Signature mismatch - REJECT (don't just decode!)
        auditLog(null, 'jwt_invalid_sig', 'IP=' . getIP());
        return null;
    }
    
    // Signature valid - now decode
    $data = json_decode(base64_decode($payload), true);
    if (!$data || !isset($data['user_id'])) return null;
    
    // Check expiry if present
    if (isset($data['exp']) && $data['exp'] < time()) {
        auditLog($data['user_id'], 'jwt_expired', '');
        return null;
    }
    
    $_SESSION['user_id'] = $data['user_id'];
    return intval($data['user_id']);
}

function getIP() { 
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Take first IP if multiple (proxy chain)
    return explode(',', $ip)[0];
}

/**
 * FIX #4: Rate Limiting - actually enforce it
 */
function rateLimit($endpoint, $maxHits = 30, $windowSec = 60) {
    global $db;
    $ip = getIP();
    $window = date('Y-m-d H:i:s', time() - $windowSec);
    
    try {
        // Clean old
        $db->query("DELETE FROM rate_limits WHERE window_start < ?", [date('Y-m-d H:i:s', time() - 3600)]);
        
        // Count
        $r = $db->fetchOne("SELECT COALESCE(SUM(hits),0) as total FROM rate_limits WHERE ip=? AND endpoint=? AND window_start > ?", [$ip, $endpoint, $window]);
        $total = intval($r['total'] ?? 0);
        
        if ($total >= $maxHits) {
            auditLog(null, 'rate_limit', "endpoint=$endpoint ip=$ip hits=$total");
            http_response_code(429);
            echo json_encode(['success'=>false,'message'=>'Quá nhiều yêu cầu. Thử lại sau.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $db->query("INSERT INTO rate_limits (ip,endpoint,hits,window_start) VALUES (?,?,1,NOW())", [$ip, $endpoint]);
    } catch (Throwable $e) {}
}

function auditLog($uid, $act, $det = '') {
    global $db;
    try {
        $db->query("INSERT INTO audit_log (user_id,action,details,ip,user_agent,created_at) VALUES (?,?,?,?,?,NOW())",
            [$uid, $act, $det, getIP(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)]);
    } catch (Throwable $e) {}
}

function ensureWallet($uid) {
    global $db;
    $w = $db->fetchOne("SELECT * FROM wallets WHERE user_id=?", [$uid]);
    if (!$w) {
        $db->query("INSERT INTO wallets (user_id,balance,created_at) VALUES (?,0,NOW())", [$uid]);
        $w = $db->fetchOne("SELECT * FROM wallets WHERE user_id=?", [$uid]);
    }
    return $w;
}

function checkPin($uid, $pin) {
    global $db;
    $w = $db->fetchOne("SELECT pin_hash,pin_attempts,locked_until FROM wallets WHERE user_id=?", [$uid]);
    if (!$w) return 'Ví không tồn tại';
    if ($w['locked_until'] && strtotime($w['locked_until']) > time()) {
        $m = ceil((strtotime($w['locked_until']) - time()) / 60);
        return "Ví bị khóa. Thử lại sau $m phút.";
    }
    if (!$w['pin_hash']) return 'Chưa thiết lập PIN.';
    if (!password_verify($pin, $w['pin_hash'])) {
        $att = intval($w['pin_attempts']) + 1;
        $lock = null;
        if ($att >= 5) $lock = date('Y-m-d H:i:s', time() + 1800);
        elseif ($att >= 3) $lock = date('Y-m-d H:i:s', time() + 300);
        $db->query("UPDATE wallets SET pin_attempts=?, locked_until=? WHERE user_id=?", [$att, $lock, $uid]);
        auditLog($uid, 'pin_fail', "att=$att ip=" . getIP());
        return 'Sai PIN. Còn ' . (5 - $att) . ' lần.' . ($lock ? ' Ví tạm khóa.' : '');
    }
    $db->query("UPDATE wallets SET pin_attempts=0, locked_until=NULL WHERE user_id=?", [$uid]);
    return null;
}

/**
 * FIX #2: CSRF Token generation & verification
 */
function generateCSRF($uid) {
    global $db;
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);
    try {
        $db->query("DELETE FROM csrf_tokens WHERE user_id=? OR expires_at < NOW()", [$uid]);
        $db->query("INSERT INTO csrf_tokens (user_id,token,expires_at) VALUES (?,?,?)", [$uid, $token, $expires]);
    } catch (Throwable $e) {}
    return $token;
}

function verifyCSRF($uid, $token) {
    global $db;
    if (empty($token)) return false;
    $row = $db->fetchOne("SELECT id FROM csrf_tokens WHERE user_id=? AND token=? AND expires_at > NOW() AND used=0", [$uid, $token]);
    if (!$row) return false;
    $db->query("UPDATE csrf_tokens SET used=1 WHERE id=?", [$row['id']]);
    return true;
}

function wOk($msg, $data = []) { echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function wErr($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

// ============================================
// GET ENDPOINTS
// ============================================
if ($method === 'GET') {

    if ($action === 'plans') {
        rateLimit('plans', 30, 60);
        $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY sort_order ASC", []);
        foreach ($plans as &$p) {
            $p['features'] = json_decode($p['features'] ?: '[]', true);
            $p['price'] = floatval($p['price']);
        }
        wOk('OK', $plans);
    }

    if ($action === '' || $action === 'info') {
        $uid = wAuth();
        if (!$uid) wErr('Đăng nhập', 401);
        rateLimit('wallet_info', 60, 60);

        $wallet = ensureWallet($uid);
        $hasPin = !empty($wallet['pin_hash']);
        $isLocked = ($wallet['locked_until'] && strtotime($wallet['locked_until']) > time());

        $sub = $db->fetchOne("SELECT us.*, sp.name as plan_name, sp.slug as plan_slug, sp.badge, sp.badge_color, sp.features FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.expires_at > NOW() ORDER BY us.expires_at DESC LIMIT 1", [$uid]);

        $txns = $db->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$uid]);
        if (!$txns) $txns = [];

        // Generate CSRF token for subsequent POST requests
        $csrf = generateCSRF($uid);

        wOk('OK', [
            'balance' => floatval($wallet['balance']),
            'total_deposit' => floatval($wallet['total_deposit'] ?? 0),
            'total_spent' => floatval($wallet['total_spent'] ?? 0),
            'has_pin' => $hasPin,
            'is_locked' => $isLocked,
            'csrf_token' => $csrf,
            'subscription' => $sub ? [
                'plan' => $sub['plan_name'], 'slug' => $sub['plan_slug'],
                'badge' => $sub['badge'], 'badge_color' => $sub['badge_color'],
                'features' => json_decode($sub['features'] ?: '[]', true),
                'expires_at' => $sub['expires_at'],
                'auto_renew' => (bool)($sub['auto_renew'] ?? 0),
            ] : null,
            'transactions' => $txns,
        ]);
    }

    if ($action === 'transactions') {
        $uid = wAuth();
        if (!$uid) wErr('Đăng nhập', 401);
        rateLimit('transactions', 30, 60);
        $page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($page - 1) * 20;
        $txns = $db->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20 OFFSET $offset", [$uid]);
        wOk('OK', $txns ?: []);
    }
}

// ============================================
// POST ENDPOINTS (all require auth + CSRF for financial ops)
// ============================================
if ($method === 'POST') {
    $uid = wAuth();
    if (!$uid) wErr('Đăng nhập', 401);

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    $csrfToken = $input['csrf_token'] ?? '';

    // SET PIN (doesn't require CSRF - first time setup)
    if ($action === 'set_pin') {
        rateLimit('set_pin', 5, 300);
        $pin = trim($input['pin'] ?? '');
        $cfm = trim($input['confirm_pin'] ?? '');
        if (strlen($pin) < 4 || strlen($pin) > 6 || !ctype_digit($pin)) wErr('PIN phải là 4-6 chữ số');
        if ($pin !== $cfm) wErr('PIN không khớp');

        $wallet = ensureWallet($uid);
        if (!empty($wallet['pin_hash'])) {
            $old = trim($input['old_pin'] ?? '');
            $err = checkPin($uid, $old);
            if ($err) wErr($err);
        }

        $hash = password_hash($pin, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->query("UPDATE wallets SET pin_hash=?, pin_attempts=0, locked_until=NULL WHERE user_id=?", [$hash, $uid]);
        auditLog($uid, 'pin_set', 'IP=' . getIP());
        wOk('Đã thiết lập mã PIN!');
    }

    // SUBSCRIBE (requires CSRF + PIN for paid)
    if ($action === 'subscribe') {
        rateLimit('subscribe', 10, 300);
        $planId = intval($input['plan_id'] ?? 0);
        $pin = trim($input['pin'] ?? '');
        if (!$planId) wErr('Chọn gói');

        $plan = $db->fetchOne("SELECT * FROM subscription_plans WHERE id=? AND is_active=1", [$planId]);
        if (!$plan) wErr('Gói không tồn tại');
        $price = floatval($plan['price']);

        // Free plan
        if ($price <= 0) {
            $db->query("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'", [$uid]);
            $db->query("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(), INTERVAL ? DAY),0)", [$uid, $planId, intval($plan['duration_days'])]);
            auditLog($uid, 'sub_free', $plan['name']);
            wOk('Đã kích hoạt gói ' . $plan['name']);
        }

        // FIX #2: Verify CSRF token for paid operations
        if (!verifyCSRF($uid, $csrfToken)) {
            auditLog($uid, 'csrf_fail', 'subscribe attempt');
            wErr('Phiên làm việc hết hạn. Vui lòng tải lại trang.');
        }

        // Verify PIN
        if (!$pin) wErr('Nhập PIN để xác nhận');
        $err = checkPin($uid, $pin);
        if ($err) wErr($err);

        // Check balance
        $wallet = ensureWallet($uid);
        if (floatval($wallet['balance']) < $price) {
            wErr('Số dư không đủ. Cần ' . number_format($price) . 'đ, hiện có ' . number_format($wallet['balance']) . 'đ');
        }

        /**
         * FIX #3: Atomic transaction with row lock
         * Use SELECT ... FOR UPDATE to lock the row, preventing race conditions
         */
        try {
            // Begin transaction
            $pdo = db()->getConnection();
            $pdo->beginTransaction();
            
            // Lock wallet row
            $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id=? FOR UPDATE");
            $stmt->execute([$uid]);
            $lockedWallet = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$lockedWallet || floatval($lockedWallet['balance']) < $price) {
                $pdo->rollBack();
                wErr('Số dư không đủ (đã verify lại)');
            }
            
            $before = floatval($lockedWallet['balance']);
            $after = $before - $price;
            
            // Deduct
            $stmt2 = $pdo->prepare("UPDATE wallets SET balance=?, total_spent=total_spent+?, updated_at=NOW() WHERE user_id=?");
            $stmt2->execute([$after, $price, $uid]);
            
            // Record transaction
            $ref = 'SUB_' . strtoupper($plan['slug']) . '_' . date('YmdHis') . '_' . $uid;
            $stmt3 = $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,`status`,created_at) VALUES (?,'payment',?,?,?,?,?,'completed',NOW())");
            $stmt3->execute([$uid, $price, $before, $after, 'Đăng ký gói ' . $plan['name'], $ref]);
            
            // Cancel old + create new subscription
            $stmt4 = $pdo->prepare("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'");
            $stmt4->execute([$uid]);
            
            $stmt5 = $pdo->prepare("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(), INTERVAL ? DAY),1)");
            $stmt5->execute([$uid, $planId, intval($plan['duration_days'])]);
            
            $pdo->commit();
            
            auditLog($uid, 'sub_paid', "plan={$plan['name']} price=$price ref=$ref balance_after=$after");
            wOk('Đăng ký thành công gói ' . $plan['name'] . '!', ['balance' => $after]);
            
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            auditLog($uid, 'sub_error', $e->getMessage());
            wErr('Lỗi giao dịch. Vui lòng thử lại.');
        }
    }

    // DEPOSIT (requires CSRF)
    if ($action === 'deposit') {
        rateLimit('deposit', 5, 300);
        
        if (!verifyCSRF($uid, $csrfToken)) {
            wErr('Phiên hết hạn. Tải lại trang.');
        }
        
        $amount = floatval($input['amount'] ?? 0);
        $bank = trim($input['bank_name'] ?? '');
        if ($amount < 10000) wErr('Tối thiểu 10.000đ');
        if ($amount > 10000000) wErr('Tối đa 10.000.000đ');

        $wallet = ensureWallet($uid);
        $ref = 'DEP_' . date('YmdHis') . '_' . $uid . '_' . bin2hex(random_bytes(4));
        $db->query("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,bank_name,`status`,created_at) VALUES (?,'deposit',?,?,?,?,?,?,'pending',NOW())",
            [$uid, $amount, $wallet['balance'], $wallet['balance'], 'Nạp tiền qua ' . ($bank ?: 'CK'), $ref, $bank]);

        auditLog($uid, 'deposit_req', "amount=$amount ref=$ref ip=" . getIP());
        wOk('Yêu cầu nạp tiền đã gửi! Admin sẽ duyệt trong 5-30 phút.', ['reference' => $ref]);
    }

    // CANCEL AUTO-RENEW
    if ($action === 'cancel_subscription') {
        rateLimit('cancel_sub', 5, 300);
        $db->query("UPDATE user_subscriptions SET auto_renew=0 WHERE user_id=? AND `status`='active'", [$uid]);
        auditLog($uid, 'cancel_renew', '');
        wOk('Đã tắt tự động gia hạn.');
    }
}

wErr('Invalid request');
