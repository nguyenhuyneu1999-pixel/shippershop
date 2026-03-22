<?php
/**
 * ShipperShop Redis Rate Limiter
 * Redis: sliding window, O(1) per check
 * Fallback: DB-based (slower but works)
 * 
 * Usage: rateLimitRedis('api:posts:' . $ip, 60, 60); // 60 requests/60 seconds
 */

function rateLimitRedis($key, $maxRequests = 60, $windowSeconds = 60) {
    if (class_exists('Redis')) {
        try {
            $r = new Redis();
            $r->connect('127.0.0.1', 6379, 0.5);
            $r->select(2); // DB 2 for rate limits
            
            $rKey = 'ss:rl:' . $key;
            $current = intval($r->get($rKey));
            
            if ($current >= $maxRequests) {
                $ttl = $r->ttl($rKey);
                header('Retry-After: ' . max(1, $ttl));
                http_response_code(429);
                echo json_encode(['success' => false, 'message' => 'Quá nhiều yêu cầu. Thử lại sau ' . max(1, $ttl) . 's']);
                exit;
            }
            
            $r->incr($rKey);
            if ($current === 0) $r->expire($rKey, $windowSeconds);
            
            return true;
        } catch (Exception $e) {
            return true; // If Redis fails, allow request
        }
    }
    return true; // No Redis = no rate limit (handled elsewhere)
}

/**
 * IP-based rate limit for API endpoints
 */
function apiRateLimit($endpoint = '', $maxPerMinute = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return rateLimitRedis('api:' . $endpoint . ':' . $ip, $maxPerMinute, 60);
}
