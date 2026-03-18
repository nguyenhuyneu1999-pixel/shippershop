<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

function wAuth() {
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

function wOk($msg, $data = []) { echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function wErr($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

function getIP() { return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

function auditLog($uid, $act, $det = '') {
    global $db;
    try { $db->query("INSERT INTO audit_log (user_id,action,details,ip,created_at) VALUES (?,?,?,?,NOW())", [$uid, $act, $det, getIP()]); } catch (Throwable $e) {}
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
    if (!$w) return 'Vi khong ton tai';
    if ($w['locked_until'] && strtotime($w['locked_until']) > time()) {
        $m = ceil((strtotime($w['locked_until']) - time()) / 60);
        return 'Vi bi khoa. Thu lai sau ' . $m . ' phut.';
    }
    if (!$w['pin_hash']) return 'Chua thiet lap PIN.';
    if (!password_verify($pin, $w['pin_hash'])) {
        $att = intval($w['pin_attempts']) + 1;
        $lock = null;
        if ($att >= 5) { $lock = date('Y-m-d H:i:s', time() + 1800); }
        elseif ($att >= 3) { $lock = date('Y-m-d H:i:s', time() + 300); }
        $db->query("UPDATE wallets SET pin_attempts=?, locked_until=? WHERE user_id=?", [$att, $lock, $uid]);
        auditLog($uid, 'pin_fail', "att=$att");
        return 'Sai PIN. Con ' . (5 - $att) . ' lan thu.' . ($lock ? ' Vi tam khoa.' : '');
    }
    $db->query("UPDATE wallets SET pin_attempts=0, locked_until=NULL WHERE user_id=?", [$uid]);
    return null; // OK
}

// ==========================================
// GET
// ==========================================
if ($method === 'GET') {

    // Plans
    if ($action === 'plans') {
        $plans = $db->fetchAll("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY sort_order ASC", []);
        foreach ($plans as &$p) {
            $p['features'] = json_decode($p['features'] ?: '[]', true);
            $p['price'] = floatval($p['price']);
        }
        wOk('OK', $plans);
    }

    // Wallet info
    if ($action === '' || $action === 'info') {
        $uid = wAuth();
        if (!$uid) wErr('Dang nhap', 401);

        $wallet = ensureWallet($uid);
        $hasPin = !empty($wallet['pin_hash']);
        $isLocked = ($wallet['locked_until'] && strtotime($wallet['locked_until']) > time());

        $sub = $db->fetchOne("SELECT us.*, sp.name as plan_name, sp.slug as plan_slug, sp.badge, sp.badge_color, sp.features FROM user_subscriptions us JOIN subscription_plans sp ON us.plan_id=sp.id WHERE us.user_id=? AND us.`status`='active' AND us.expires_at > NOW() ORDER BY us.expires_at DESC LIMIT 1", [$uid]);

        $txns = $db->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 10", [$uid]);
        if (!$txns) $txns = [];

        wOk('OK', [
            'balance' => floatval($wallet['balance']),
            'total_deposit' => floatval($wallet['total_deposit'] ?? 0),
            'total_spent' => floatval($wallet['total_spent'] ?? 0),
            'has_pin' => $hasPin,
            'is_locked' => $isLocked,
            'subscription' => $sub ? [
                'plan' => $sub['plan_name'],
                'slug' => $sub['plan_slug'],
                'badge' => $sub['badge'],
                'badge_color' => $sub['badge_color'],
                'features' => json_decode($sub['features'] ?: '[]', true),
                'expires_at' => $sub['expires_at'],
                'auto_renew' => (bool)($sub['auto_renew'] ?? 0),
            ] : null,
            'transactions' => $txns,
        ]);
    }

    // Transaction history
    if ($action === 'transactions') {
        $uid = wAuth();
        if (!$uid) wErr('Dang nhap', 401);
        $page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($page - 1) * 20;
        $txns = $db->fetchAll("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 20 OFFSET $offset", [$uid]);
        wOk('OK', $txns ?: []);
    }
}

// ==========================================
// POST
// ==========================================
if ($method === 'POST') {
    $uid = wAuth();
    if (!$uid) wErr('Dang nhap', 401);

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    // SET PIN
    if ($action === 'set_pin') {
        $pin = trim($input['pin'] ?? '');
        $cfm = trim($input['confirm_pin'] ?? '');
        if (strlen($pin) < 4 || strlen($pin) > 6 || !ctype_digit($pin)) wErr('PIN phai la 4-6 chu so');
        if ($pin !== $cfm) wErr('PIN khong khop');

        $wallet = ensureWallet($uid);
        if (!empty($wallet['pin_hash'])) {
            $old = trim($input['old_pin'] ?? '');
            $err = checkPin($uid, $old);
            if ($err) wErr($err);
        }

        $hash = password_hash($pin, PASSWORD_BCRYPT);
        $db->query("UPDATE wallets SET pin_hash=?, pin_attempts=0, locked_until=NULL WHERE user_id=?", [$hash, $uid]);
        auditLog($uid, 'pin_set', '');
        wOk('Da thiet lap ma PIN!');
    }

    // SUBSCRIBE
    if ($action === 'subscribe') {
        $planId = intval($input['plan_id'] ?? 0);
        $pin = trim($input['pin'] ?? '');
        if (!$planId) wErr('Chon goi');

        $plan = $db->fetchOne("SELECT * FROM subscription_plans WHERE id=? AND is_active=1", [$planId]);
        if (!$plan) wErr('Goi khong ton tai');

        $price = floatval($plan['price']);

        // Free plan
        if ($price <= 0) {
            $db->query("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'", [$uid]);
            $db->query("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(), INTERVAL ? DAY),0)", [$uid, $planId, intval($plan['duration_days'])]);
            auditLog($uid, 'sub_free', $plan['name']);
            wOk('Da kich hoat goi ' . $plan['name']);
        }

        // Paid - verify PIN
        if (!$pin) wErr('Nhap PIN de xac nhan');
        $err = checkPin($uid, $pin);
        if ($err) wErr($err);

        $wallet = ensureWallet($uid);
        if (floatval($wallet['balance']) < $price) {
            wErr('So du khong du. Can ' . number_format($price) . 'd, hien co ' . number_format($wallet['balance']) . 'd');
        }

        // Deduct
        $before = floatval($wallet['balance']);
        $after = $before - $price;
        $db->query("UPDATE wallets SET balance=balance-?, total_spent=total_spent+?, updated_at=NOW() WHERE user_id=? AND balance>=?", [$price, $price, $uid, $price]);

        $ref = 'SUB_' . strtoupper($plan['slug']) . '_' . date('YmdHis') . '_' . $uid;
        $db->query("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,`status`,created_at) VALUES (?,'payment',?,?,?,?,?,'completed',NOW())", [$uid, $price, $before, $after, 'Dang ky goi ' . $plan['name'], $ref]);

        $db->query("UPDATE user_subscriptions SET `status`='cancelled' WHERE user_id=? AND `status`='active'", [$uid]);
        $db->query("INSERT INTO user_subscriptions (user_id,plan_id,`status`,started_at,expires_at,auto_renew) VALUES (?,?,'active',NOW(),DATE_ADD(NOW(), INTERVAL ? DAY),1)", [$uid, $planId, intval($plan['duration_days'])]);

        auditLog($uid, 'sub_paid', "plan={$plan['name']} price=$price ref=$ref");
        wOk('Dang ky thanh cong goi ' . $plan['name'] . '!', ['balance' => $after]);
    }

    // DEPOSIT
    if ($action === 'deposit') {
        $amount = floatval($input['amount'] ?? 0);
        $bank = trim($input['bank_name'] ?? '');
        if ($amount < 10000) wErr('Toi thieu 10.000d');
        if ($amount > 10000000) wErr('Toi da 10.000.000d');

        $wallet = ensureWallet($uid);
        $ref = 'DEP_' . date('YmdHis') . '_' . $uid . '_' . rand(1000, 9999);
        $db->query("INSERT INTO wallet_transactions (user_id,type,amount,balance_before,balance_after,description,reference_id,bank_name,`status`,created_at) VALUES (?,'deposit',?,?,?,?,?,?,'pending',NOW())", [$uid, $amount, $wallet['balance'], $wallet['balance'], 'Nap tien qua ' . ($bank ?: 'CK'), $ref, $bank]);

        auditLog($uid, 'deposit_req', "amount=$amount ref=$ref");
        wOk('Yeu cau nap tien da gui! Admin se duyet trong 5-30 phut.', ['reference' => $ref]);
    }

    // CANCEL AUTO-RENEW
    if ($action === 'cancel_subscription') {
        $db->query("UPDATE user_subscriptions SET auto_renew=0 WHERE user_id=? AND `status`='active'", [$uid]);
        auditLog($uid, 'cancel_renew', '');
        wOk('Da tat tu dong gia han.');
    }
}

wErr('Invalid request');
