<?php
header('Content-Type: application/json');
$result = [];

// Test Redis connection
if (class_exists('Redis')) {
    try {
        $r = new Redis();
        $r->connect('127.0.0.1', 6379, 0.3);
        $r->select(1);
        
        // Test set/get
        $r->setex('test:cache:key1', 60, 'hello');
        $v = $r->get('test:cache:key1');
        $result['redis_test'] = $v === 'hello' ? 'OK' : 'FAIL: ' . var_export($v, true);
        
        // Check for api cache keys
        $keys = $r->keys('ss:ac:*');
        $result['api_cache_keys'] = count($keys);
        $result['sample_keys'] = array_slice($keys, 0, 5);
        
        $r->del('test:cache:key1');
    } catch (Exception $e) {
        $result['redis_error'] = $e->getMessage();
    }
} else {
    $result['redis'] = 'NOT AVAILABLE';
}

// Check file cache
$dir = '/tmp/ss_api_cache';
$result['file_cache_dir'] = is_dir($dir);
$result['file_cache_count'] = is_dir($dir) ? count(glob($dir . '/*.json')) : 0;

echo json_encode($result, JSON_PRETTY_PRINT);
