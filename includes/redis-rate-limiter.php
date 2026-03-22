<?php
/**
 * ShipperShop Rate Limiter — Redis-backed (via SmartCache) with file fallback
 * NO new Redis connections — reuses SmartCache singleton
 */

function apiRateLimit($endpoint = '', $maxPerMinute = 120) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'rl:' . $endpoint . ':' . $ip . ':' . floor(time() / 60);
    
    // Try SmartCache (Redis or File, auto-detect)
    if (class_exists('SmartCache')) {
        try {
            require_once __DIR__ . '/smart-cache.php';
            $sc = scache();
            $current = intval($sc->get($key) ?: 0);
            if ($current >= $maxPerMinute) {
                http_response_code(429);
                header('Retry-After: ' . (60 - (time() % 60)));
                echo json_encode(['success' => false, 'message' => 'Quá nhiều yêu cầu. Thử lại sau.']);
                exit;
            }
            $sc->set($key, $current + 1, 120); // 2 min TTL
            // Rate limit headers
            header('X-RateLimit-Limit: ' . $maxPerMinute);
            header('X-RateLimit-Remaining: ' . max(0, $maxPerMinute - $current - 1));
            return true;
        } catch (Throwable $e) {
            return true; // If fails, allow request
        }
    }
    return true; // No SmartCache = no rate limit
}
