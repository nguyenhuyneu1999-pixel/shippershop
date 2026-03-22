<?php
/**
 * ShipperShop API Cache Layer
 * AUTO-DETECT: Redis (fast) → File (fallback)
 * 
 * Usage: api_try_cache('feed_page1', 30);
 * If cache HIT → echo JSON + exit (0 DB queries)
 * If cache MISS → continue, then api_cache_save() at response
 */

$_apiCacheRedis = null;
$_apiCacheKey = null;

function _getRedis() {
    global $_apiCacheRedis;
    if ($_apiCacheRedis !== null) return $_apiCacheRedis;
    if (class_exists('Redis')) {
        try {
            $r = new Redis();
            $r->connect('127.0.0.1', 6379, 1);
            $r->select(1); // DB 1 for API cache
            $_apiCacheRedis = $r;
            return $r;
        } catch (Exception $e) {
            $_apiCacheRedis = false;
        }
    }
    $_apiCacheRedis = false;
    return false;
}

function api_try_cache($key, $ttl = 30) {
    global $_apiCacheKey;
    $_apiCacheKey = 'ss:api:' . $key;
    
    $r = _getRedis();
    if ($r) {
        // Redis cache
        $cached = $r->get($_apiCacheKey);
        if ($cached !== false) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Cache: HIT-Redis');
            echo $cached;
            exit;
        }
        // Register shutdown to save response
        ob_start();
        register_shutdown_function(function() use ($r, $key, $ttl) {
            $output = ob_get_clean();
            if ($output && strpos($output, '"success":true') !== false) {
                $r->setex('ss:api:' . $key, $ttl, $output);
            }
            echo $output;
        });
        return;
    }
    
    // File cache fallback
    $dir = sys_get_temp_dir() . '/ss_api_cache';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $file = $dir . '/' . md5($key) . '.json';
    
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Cache: HIT-File');
        readfile($file);
        exit;
    }
    
    ob_start();
    register_shutdown_function(function() use ($file, $ttl) {
        $output = ob_get_clean();
        if ($output && strpos($output, '"success":true') !== false) {
            @file_put_contents($file, $output);
        }
        echo $output;
    });
}

function api_cache_flush($prefix = '') {
    $r = _getRedis();
    if ($r) {
        if ($prefix) {
            $keys = $r->keys('ss:api:' . $prefix . '*');
            if ($keys) $r->del($keys);
        } else {
            // Flush all API cache
            $keys = $r->keys('ss:api:*');
            if ($keys) $r->del($keys);
        }
        return;
    }
    
    // File fallback
    $dir = sys_get_temp_dir() . '/ss_api_cache';
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.json') as $f) @unlink($f);
    }
}
