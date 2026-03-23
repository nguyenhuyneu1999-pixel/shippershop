<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/api-cache.php';
header('Content-Type: application/json');

$result = [];

// Test file cache speed
$start = microtime(true);
api_cache_set('test_speed', '{"test": true}', 60);
$result['cache_write_ms'] = round((microtime(true) - $start) * 1000, 2);

$start = microtime(true);
$val = api_cache_get('test_speed');
$result['cache_read_ms'] = round((microtime(true) - $start) * 1000, 2);
$result['cache_hit'] = $val !== null;

// Test Redis
$result['redis_available'] = class_exists('Redis');
if (class_exists('Redis')) {
    try {
        $start = microtime(true);
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.3);
        $r->select(1);
        $result['redis_connect_ms'] = round((microtime(true) - $start) * 1000, 2);
        
        $start = microtime(true);
        $r->setex('test:speed', 60, 'hello');
        $result['redis_write_ms'] = round((microtime(true) - $start) * 1000, 2);
        
        $start = microtime(true);
        $v = $r->get('test:speed');
        $result['redis_read_ms'] = round((microtime(true) - $start) * 1000, 2);
        
        // Check api cache keys
        $keys = $r->keys('ss:ac:*');
        $result['redis_api_cache_keys'] = count($keys);
    } catch (Exception $e) {
        $result['redis_error'] = $e->getMessage();
    }
}

// Check file cache dir
$dir = '/tmp/ss_api_cache';
$result['file_cache_dir'] = is_dir($dir);
$result['file_cache_count'] = is_dir($dir) ? count(glob($dir . '/*.json')) : 0;

// Check SmartCache
if (file_exists(__DIR__ . '/../includes/smart-cache.php')) {
    require_once __DIR__ . '/../includes/smart-cache.php';
    $sc = scache();
    $start = microtime(true);
    $sc->set('test_sc', 'hello', 60);
    $result['smartcache_write_ms'] = round((microtime(true) - $start) * 1000, 2);
    
    $start = microtime(true);
    $v = $sc->get('test_sc');
    $result['smartcache_read_ms'] = round((microtime(true) - $start) * 1000, 2);
    $result['smartcache_mode'] = $sc->getMode();
}

// PHP overhead
$result['php_version'] = PHP_VERSION;
$result['opcache'] = function_exists('opcache_get_status') ? (opcache_get_status(false)['opcache_enabled'] ?? false) : false;

echo json_encode($result, JSON_PRETTY_PRINT);
