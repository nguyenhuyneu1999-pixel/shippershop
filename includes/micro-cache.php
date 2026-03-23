<?php
/**
 * ShipperShop Micro Cache — Ultra-fast response cache
 * Runs BEFORE config.php, db.php, session_start
 * Zero dependencies, file-based only, ~1ms overhead
 * 
 * Usage (at TOP of API file, before any require):
 *   require_once __DIR__ . '/../includes/micro-cache.php';
 *   if (microCacheServe('feed_' . md5($_SERVER['QUERY_STRING'] ?? ''), 15)) exit;
 */

function microCacheServe($key, $ttl = 30) {
    // Only cache GET requests without auth
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') return false;
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) return false;
    
    $dir = '/tmp/ss_mc';
    $file = $dir . '/' . md5($key) . '.cache';
    
    if (!file_exists($file)) return false;
    
    $data = @file_get_contents($file);
    if ($data === false) return false;
    
    // First 10 bytes = expiry timestamp
    $exp = intval(substr($data, 0, 10));
    if ($exp < time()) {
        @unlink($file);
        return false;
    }
    
    $body = substr($data, 10);
    
    // Send response with minimal headers
    header('Content-Type: application/json; charset=utf-8');
    header('X-Cache: MICRO-HIT');
    header('Content-Length: ' . strlen($body));
    
    // ETag for 304
    $etag = '"' . md5($body) . '"';
    header('ETag: ' . $etag);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        return true;
    }
    
    echo $body;
    return true;
}

function microCacheSave($key, $body, $ttl = 30) {
    $dir = '/tmp/ss_mc';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $file = $dir . '/' . md5($key) . '.cache';
    $data = str_pad(time() + $ttl, 10, '0', STR_PAD_LEFT) . $body;
    @file_put_contents($file, $data, LOCK_EX);
}
