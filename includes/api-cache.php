<?php
/**
 * ShipperShop API Cache — Redis with File fallback
 * SIMPLE approach: check cache → if HIT echo+exit, if MISS continue
 * After response built, call api_cache_save() manually
 */

function _getCacheRedis() {
    static $r = null, $tried = false;
    if ($tried) return $r;
    $tried = true;
    if (class_exists('Redis')) {
        try {
            $r = new Redis();
            $r->connect('127.0.0.1', 6379, 0.5);
            $r->select(1);
            return $r;
        } catch (Exception $e) { $r = null; }
    }
    return null;
}

function api_try_cache($key, $ttl = 30) {
    $fullKey = 'ss:api:' . $key;
    
    // Try Redis
    $r = _getCacheRedis();
    if ($r) {
        try {
            $cached = $r->get($fullKey);
            if ($cached !== false) {
                header('Content-Type: application/json; charset=utf-8');
                header('X-Cache: HIT');
                echo $cached;
                exit;
            }
        } catch (Exception $e) {}
    }
    
    // Try file cache
    $dir = sys_get_temp_dir() . '/ss_cache';
    $file = $dir . '/' . md5($key) . '.json';
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Cache: HIT-F');
        readfile($file);
        exit;
    }
    
    // Store key+ttl for api_cache_save()
    $GLOBALS['_ss_cache_key'] = $key;
    $GLOBALS['_ss_cache_ttl'] = $ttl;
}

/**
 * Call this right before echo json response in success/error functions
 * Or use register_shutdown approach only when safe
 */
function api_cache_save($jsonOutput) {
    $key = $GLOBALS['_ss_cache_key'] ?? null;
    $ttl = $GLOBALS['_ss_cache_ttl'] ?? 30;
    if (!$key || strpos($jsonOutput, '"success":true') === false) return;
    
    $fullKey = 'ss:api:' . $key;
    
    // Save to Redis
    $r = _getCacheRedis();
    if ($r) {
        try { $r->setex($fullKey, $ttl, $jsonOutput); } catch (Exception $e) {}
    }
    
    // Save to file
    $dir = sys_get_temp_dir() . '/ss_cache';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents($dir . '/' . md5($key) . '.json', $jsonOutput);
}

function api_cache_flush($prefix = '') {
    $r = _getCacheRedis();
    if ($r) {
        try {
            $keys = $r->keys('ss:api:' . $prefix . '*');
            if ($keys) $r->del($keys);
        } catch (Exception $e) {}
    }
    // File cleanup
    $dir = sys_get_temp_dir() . '/ss_cache';
    if ($prefix === '' && is_dir($dir)) {
        foreach (glob($dir . '/*.json') as $f) @unlink($f);
    }
}
