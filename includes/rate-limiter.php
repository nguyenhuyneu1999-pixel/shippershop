<?php
/**
 * ShipperShop Rate Limiter — DB-based (shared hosting compatible)
 * Usage: if(!rate_check('login:'.$ip, 5, 300)) { error('Too many attempts'); }
 */

/**
 * Check if action is allowed under rate limit
 * @param string $key e.g. "login:192.168.1.1" or "post:user:5"
 * @param int $max max attempts allowed
 * @param int $window time window in seconds
 * @return bool true if allowed, false if exceeded
 */
function rate_check($key, $max, $window) {
    $d = db();
    $cutoff = date('Y-m-d H:i:s', time() - $window);
    
    // Cleanup old entries
    try {
        $d->query("DELETE FROM rate_limits WHERE created_at < ?", [$cutoff]);
    } catch (\Throwable $e) {}
    
    // Count recent attempts
    $row = $d->fetchOne(
        "SELECT COUNT(*) as c FROM rate_limits WHERE `key` = ? AND created_at > ?",
        [$key, $cutoff]
    );
    $count = intval($row['c'] ?? 0);
    
    if ($count >= $max) {
        return false; // Rate limited
    }
    
    // Record this attempt
    try {
        $d->query(
            "INSERT INTO rate_limits (`key`, created_at) VALUES (?, NOW())",
            [$key]
        );
    } catch (\Throwable $e) {}
    
    return true; // Allowed
}

/**
 * Get remaining attempts
 * @param string $key
 * @param int $max
 * @param int $window seconds
 * @return int remaining attempts
 */
function rate_remaining($key, $max, $window) {
    $d = db();
    $cutoff = date('Y-m-d H:i:s', time() - $window);
    $row = $d->fetchOne(
        "SELECT COUNT(*) as c FROM rate_limits WHERE `key` = ? AND created_at > ?",
        [$key, $cutoff]
    );
    return max(0, $max - intval($row['c'] ?? 0));
}

/**
 * Reset rate limit for a key
 * @param string $key
 */
function rate_reset($key) {
    try {
        db()->query("DELETE FROM rate_limits WHERE `key` = ?", [$key]);
    } catch (\Throwable $e) {}
}

/**
 * Check rate and return 429 if exceeded
 * @param string $key
 * @param int $max
 * @param int $window
 */
function rate_enforce($key, $max, $window) {
    if (!rate_check($key, $max, $window)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.',
            'retry_after' => $window
        ]);
        exit;
    }
}
