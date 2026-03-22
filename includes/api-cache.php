<?php
/**
 * ShipperShop API Cache — Redis + File
 * 
 * HOW IT WORKS:
 * 1. api_try_cache('key', 30) → checks Redis/file, if HIT → echo + exit
 * 2. If MISS → stores key in $GLOBALS, continues to normal code
 * 3. jsonResponse() checks $GLOBALS → saves to Redis/file before echo
 * 
 * NO ob_start, NO register_shutdown_function, NO magic
 */

function _ssRedis() {
    static $r = null, $tried = false;
    if ($tried) return $r;
    $tried = true;
    if (!class_exists('Redis')) { $r = false; return false; }
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.5); // 500ms timeout
        $r->select(1);
    } catch (Exception $e) { $r = false; }
    return $r;
}

function api_try_cache($key, $ttl = 30) {
    $rKey = 'ss:c:' . $key;
    
    // 1. Try Redis
    $r = _ssRedis();
    if ($r) {
        try {
            $v = $r->get($rKey);
            if ($v !== false) {
                header('Content-Type: application/json; charset=utf-8');
                header('X-Cache: HIT');
                echo $v;
                exit;
            }
        } catch (Exception $e) {}
    }
    
    // 2. Try file
    $f = sys_get_temp_dir() . '/ss_c/' . md5($key) . '.j';
    if (file_exists($f) && (time() - filemtime($f)) < $ttl) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Cache: HIT-F');
        readfile($f);
        exit;
    }
    
    // 3. MISS — store for later save
    $GLOBALS['_ssCacheKey'] = $key;
    $GLOBALS['_ssCacheTTL'] = $ttl;
}

/**
 * Save response to cache — called from jsonResponse()
 */
function _ssCacheSave($json) {
    $key = $GLOBALS['_ssCacheKey'] ?? null;
    $ttl = $GLOBALS['_ssCacheTTL'] ?? 30;
    if (!$key) return;
    
    // Only cache successful responses
    if (strpos($json, '"success":true') === false) return;
    
    $rKey = 'ss:c:' . $key;
    
    // Redis
    $r = _ssRedis();
    if ($r) {
        try { $r->setex($rKey, $ttl, $json); } catch (Exception $e) {}
    }
    
    // File
    $dir = sys_get_temp_dir() . '/ss_c';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents($dir . '/' . md5($key) . '.j', $json);
    
    // Clear globals
    unset($GLOBALS['_ssCacheKey']);
}

/**
 * Flush cache by prefix
 */
function api_cache_flush($prefix = '') {
    $r = _ssRedis();
    if ($r) {
        try {
            $keys = $r->keys('ss:c:' . $prefix . '*');
            if ($keys && count($keys) > 0) $r->del($keys);
        } catch (Exception $e) {}
    }
    $dir = sys_get_temp_dir() . '/ss_c';
    if (is_dir($dir) && !$prefix) {
        foreach (glob($dir . '/*.j') as $f) @unlink($f);
    }
}
