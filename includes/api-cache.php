<?php
/**
 * ShipperShop API Cache Layer — High-performance response caching
 * Giảm DB queries từ 35-65 xuống 0-1 cho cached responses
 * 
 * Usage:
 *   $cached = api_cache_get('feed_hot_1');
 *   if ($cached) { echo $cached; exit; }
 *   // ... process ...
 *   api_cache_set('feed_hot_1', $response, 30);
 */

define('API_CACHE_DIR', '/tmp/ss_api_cache');

function api_cache_get($key) {
    // Try Redis first (if available)
    try {
        if (class_exists('Redis')) {
            static $r = null, $rtried = false;
            if (!$rtried) { $rtried = true; try { $r = new Redis(); $r->connect('127.0.0.1', 6379, 0.3); $r->select(1); } catch (Exception $e) { $r = null; } }
            if ($r) { $v = $r->get('ss:ac:' . $key); if ($v !== false) return $v; }
        }
    } catch (Throwable $e) {}
    // File fallback
    $path = API_CACHE_DIR . '/' . md5($key) . '.json';
    if (!file_exists($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = @json_decode($raw, true);
    if (!$data || !isset($data['exp']) || $data['exp'] < time()) {
        @unlink($path);
        return null;
    }
    return $data['body'];
}

function api_cache_set($key, $body, $ttl = 30) {
    // Save to Redis (fast)
    try {
        if (class_exists('Redis')) {
            static $r2 = null, $r2tried = false;
            if (!$r2tried) { $r2tried = true; try { $r2 = new Redis(); $r2->connect('127.0.0.1', 6379, 0.3); $r2->select(1); } catch (Exception $e) { $r2 = null; } }
            if ($r2) $r2->setex('ss:ac:' . $key, $ttl, $body);
        }
    } catch (Throwable $e) {}
    // Save to file (backup)
    if (!is_dir(API_CACHE_DIR)) @mkdir(API_CACHE_DIR, 0755, true);
    $path = API_CACHE_DIR . '/' . md5($key) . '.json';
    $data = json_encode(['exp' => time() + $ttl, 'body' => $body, 'key' => $key]);
    @file_put_contents($path, $data, LOCK_EX);
}

function api_cache_del($key) {
    $path = API_CACHE_DIR . '/' . md5($key) . '.json';
    @unlink($path);
}

function api_cache_flush($prefix = '') {
    // Flush Redis
    try {
        if (class_exists('Redis')) {
            $rf = new Redis(); $rf->connect('127.0.0.1', 6379, 0.3); $rf->select(1);
            $keys = $rf->keys('ss:ac:' . $prefix . '*');
            if ($keys && count($keys)) $rf->del($keys);
        }
    } catch (Throwable $e) {}
    if (!is_dir(API_CACHE_DIR)) return;
    // If prefix, need to check stored keys
    $files = glob(API_CACHE_DIR . '/*.json');
    $count = 0;
    foreach ($files as $f) {
        if ($prefix) {
            $data = @json_decode(@file_get_contents($f), true);
            if ($data && isset($data['key']) && strpos($data['key'], $prefix) === 0) {
                @unlink($f);
                $count++;
            }
        } else {
            @unlink($f);
            $count++;
        }
    }
    return $count;
}

/**
 * Cache an entire API response with automatic JSON output
 * If cache hit: echo cached JSON + exit (0 DB queries!)
 * If cache miss: return false, caller processes normally
 */
function api_try_cache($key, $ttl = 30) {
    $cached = api_cache_get($key);
    if ($cached !== null) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Cache: HIT');
        header('X-Cache-Key: ' . substr(md5($key), 0, 8));
        // ETag for 304 on cache HIT too
        $etag = '"' . md5($cached) . '"';
        header('ETag: ' . $etag);
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            exit;
        }
        header('Content-Length: ' . strlen($cached));
        echo $cached;
        exit;
    }
    header('X-Cache: MISS');
    // Store for auto-save in jsonResponse
    $GLOBALS['_ssPendingCacheKey'] = $key;
    $GLOBALS['_ssPendingCacheTTL'] = $ttl;
    return false;
}

/**
 * Save response to cache (call after generating response)
 */
function api_save_cache($key, $responseArray, $ttl = 30) {
    $json = json_encode($responseArray, JSON_UNESCAPED_UNICODE);
    api_cache_set($key, $json, $ttl);
    return $json;
}
