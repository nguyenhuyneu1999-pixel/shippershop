<?php
// Load SmartCache for Redis/File auto-detect
require_once __DIR__ . '/smart-cache.php';
/**
 * ShipperShop API Cache Layer v3
 * Uses SmartCache (which already works!) — NO new Redis connection
 * 
 * File-based cache that's fast enough for shared hosting:
 * - HIT: 0 DB queries, ~2ms (file read)
 * - On VPS: SmartCache auto-uses Redis
 */

function api_try_cache($key, $ttl = 30) {
    // Use SmartCache if available (handles Redis/File automatically)
    if (class_exists('SmartCache')) {
        try {
            $cached = scache()->get('api:' . $key);
            if ($cached !== null && $cached !== false) {
                header('Content-Type: application/json; charset=utf-8');
                header('X-Cache: HIT');
                echo $cached;
                exit;
            }
        } catch (Throwable $e) {}
    }
    
    // File cache fallback
    $dir = sys_get_temp_dir() . '/ss_api_cache';
    $file = $dir . '/' . md5($key) . '.json';
    if (file_exists($file) && (time() - filemtime($file)) < $ttl) {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Cache: HIT-F');
        readfile($file);
        exit;
    }
    
    // MISS — store for later
    $GLOBALS['_ssCK'] = $key;
    $GLOBALS['_ssTTL'] = $ttl;
}

/**
 * Save to cache — call manually or hook into response functions
 */
function _ssCacheSave($json) {
    $key = $GLOBALS['_ssCK'] ?? null;
    $ttl = $GLOBALS['_ssTTL'] ?? 30;
    if (!$key || strpos($json, '"success":true') === false) return;
    
    // SmartCache (Redis or File, auto-detect)
    if (class_exists('SmartCache')) {
        try { scache()->set('api:' . $key, $json, $ttl); } catch (Throwable $e) {}
    }
    
    // Also file cache (always)
    $dir = sys_get_temp_dir() . '/ss_api_cache';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents($dir . '/' . md5($key) . '.json', $json);
    
    unset($GLOBALS['_ssCK']);
}

function api_cache_flush($prefix = '') {
    // SmartCache flush
    if (class_exists('SmartCache')) {
        try {
            $sc = scache();
            if ($sc->isRedis()) {
                // Redis: pattern delete
                $r = $sc->getRedis();
                if ($r) {
                    $keys = $r->keys('ss:cache:api:' . $prefix . '*');
                    if ($keys && count($keys)) $r->del($keys);
                }
            }
        } catch (Throwable $e) {}
    }
    
    // File cache flush
    $dir = sys_get_temp_dir() . '/ss_api_cache';
    if (is_dir($dir)) {
        foreach (glob($dir . '/*.json') as $f) @unlink($f);
    }
}
