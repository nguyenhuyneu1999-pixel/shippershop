<?php
/**
 * ShipperShop Cache Service — File-based (shared hosting compatible)
 * Usage: cache_get('feed_hot_1'), cache_set('feed_hot_1', $data, 30), cache_del('feed_hot_1')
 */

define('SS_CACHE_DIR', '/tmp/ss_cache');

function _cache_path($key) {
    if (!is_dir(SS_CACHE_DIR)) {
        @mkdir(SS_CACHE_DIR, 0755, true);
    }
    return SS_CACHE_DIR . '/' . md5($key) . '.cache';
}

/**
 * Get cached value
 * @param string $key
 * @return mixed|null
 */
function cache_get($key) {
    $path = _cache_path($key);
    if (!file_exists($path)) return null;
    
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    
    $data = @unserialize($raw);
    if ($data === false || !isset($data['exp']) || !isset($data['val'])) {
        @unlink($path);
        return null;
    }
    
    // Check expiry
    if ($data['exp'] > 0 && time() > $data['exp']) {
        @unlink($path);
        return null;
    }
    
    return $data['val'];
}

/**
 * Set cache value
 * @param string $key
 * @param mixed $value
 * @param int $ttl seconds (default 300 = 5 min)
 */
function cache_set($key, $value, $ttl = 300) {
    $path = _cache_path($key);
    $data = [
        'exp' => $ttl > 0 ? time() + $ttl : 0,
        'val' => $value
    ];
    @file_put_contents($path, serialize($data), LOCK_EX);
}

/**
 * Delete cached value
 * @param string $key
 */
function cache_del($key) {
    $path = _cache_path($key);
    if (file_exists($path)) @unlink($path);
}

/**
 * Delete all cache entries matching prefix
 * @param string $prefix
 */
function cache_del_prefix($prefix) {
    if (!is_dir(SS_CACHE_DIR)) return;
    $files = glob(SS_CACHE_DIR . '/*.cache');
    // Can't match by prefix with md5, so clear all
    // For prefix-based invalidation, use cache_del with exact keys
    foreach ($files as $f) @unlink($f);
}

/**
 * Clear all cache
 */
function cache_clear() {
    if (!is_dir(SS_CACHE_DIR)) return;
    $files = glob(SS_CACHE_DIR . '/*.cache');
    foreach ($files as $f) @unlink($f);
}

/**
 * Get or set pattern — fetch from cache, or compute and cache
 * @param string $key
 * @param callable $compute function that returns data
 * @param int $ttl
 * @return mixed
 */
function cache_remember($key, $compute, $ttl = 300) {
    $cached = cache_get($key);
    if ($cached !== null) return $cached;
    
    $value = $compute();
    cache_set($key, $value, $ttl);
    return $value;
}
