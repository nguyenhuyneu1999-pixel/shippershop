<?php
/**
 * ShipperShop Request Validator
 * Validate and sanitize API inputs consistently
 * Prevents SQL injection, XSS, invalid data
 */

function validateInt($value, $min = 0, $max = PHP_INT_MAX, $default = 0) {
    $v = intval($value ?? $default);
    return max($min, min($max, $v));
}

function validateString($value, $maxLen = 1000, $default = '') {
    $v = trim($value ?? $default);
    if (mb_strlen($v) > $maxLen) $v = mb_substr($v, 0, $maxLen);
    return $v;
}

function validateEmail($value) {
    $v = trim($value ?? '');
    return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
}

function validatePage($page, $limit = 20, $maxLimit = 50) {
    $p = max(1, intval($page ?? 1));
    $l = min(max(1, intval($limit)), $maxLimit);
    $offset = ($p - 1) * $l;
    return ['page' => $p, 'limit' => $l, 'offset' => $offset];
}

function validateSort($sort, $allowed = ['new', 'hot', 'top', 'following']) {
    return in_array($sort, $allowed) ? $sort : $allowed[0];
}

function sanitizeOutput($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Rate limit check using DB (works on shared hosting)
 * Returns true if allowed, false if rate limited
 */
function checkRateLimit($key, $maxRequests = 60, $windowSeconds = 60) {
    try {
        $d = db();
        $now = time();
        $windowStart = date('Y-m-d H:i:s', $now - $windowSeconds);
        
        // Clean old entries
        $d->query("DELETE FROM rate_limits WHERE created_at < ?", [$windowStart]);
        
        // Count recent requests
        $count = intval($d->fetchOne("SELECT COUNT(*) as c FROM rate_limits WHERE `key` = ? AND created_at >= ?", [$key, $windowStart])['c'] ?? 0);
        
        if ($count >= $maxRequests) {
            return false; // Rate limited
        }
        
        // Record this request
        $d->query("INSERT INTO rate_limits (`key`, created_at) VALUES (?, NOW())", [$key]);
        return true;
    } catch (Throwable $e) {
        return true; // If DB error, allow request (don't block users)
    }
}
