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
        echo $cached;
        exit;
    }
    header('X-Cache: MISS');
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
