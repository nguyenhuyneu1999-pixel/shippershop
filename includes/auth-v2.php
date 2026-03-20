<?php
/**
 * ShipperShop Auth Service v2 — Enhanced authentication helpers
 * Supplements api/auth-check.php with admin check, user caching, banned check
 * Usage: require_once 'includes/auth-v2.php';
 *        $uid = require_auth();      // 401 if not logged in
 *        $uid = optional_auth();     // null if not logged in
 *        $uid = require_admin();     // 403 if not admin
 */

if (!function_exists('verifyJWT')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/functions.php';
}

/**
 * Get authenticated user ID from session or JWT
 * @return int|null user_id or null
 */
function _get_auth_uid() {
    // Session first
    if (!empty($_SESSION['user_id'])) {
        return intval($_SESSION['user_id']);
    }
    
    // JWT Bearer token
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization']
        ?? $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? '';
    
    if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
        $data = verifyJWT($matches[1]);
        if ($data && isset($data['user_id'])) {
            $_SESSION['user_id'] = $data['user_id'];
            return intval($data['user_id']);
        }
    }
    
    return null;
}

/**
 * Require authentication — return user_id or exit 401
 * @return int user_id
 */
function require_auth() {
    $uid = _get_auth_uid();
    if (!$uid) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
        exit;
    }
    
    // Check if banned
    $user = db()->fetchOne("SELECT banned_until FROM users WHERE id = ?", [$uid]);
    if ($user && $user['banned_until'] && strtotime($user['banned_until']) > time()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Tài khoản đã bị khóa']);
        exit;
    }
    
    return $uid;
}

/**
 * Optional authentication — return user_id or null
 * @return int|null
 */
function optional_auth() {
    return _get_auth_uid();
}

/**
 * Require admin role — return user_id or exit 403
 * @return int user_id
 */
function require_admin() {
    $uid = require_auth();
    $user = db()->fetchOne("SELECT role FROM users WHERE id = ?", [$uid]);
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
        exit;
    }
    return $uid;
}

/**
 * Get current user info (cached per request)
 * @return array|null user data
 */
function current_user() {
    static $cached = null;
    if ($cached !== null) return $cached ?: null;
    
    $uid = _get_auth_uid();
    if (!$uid) { $cached = false; return null; }
    
    $user = db()->fetchOne(
        "SELECT id, fullname, username, email, avatar, cover_image, bio, shipping_company, role, 
                total_success, total_posts, banned_until, settings, created_at
         FROM users WHERE id = ?", [$uid]
    );
    
    $cached = $user ?: false;
    return $user ?: null;
}

/**
 * Get client IP address
 * @return string
 */
function client_ip() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
