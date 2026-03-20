<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    require_once __DIR__.'/../includes/db.php';
    require_once __DIR__.'/../includes/cache.php';
    require_once __DIR__.'/../includes/rate-limiter.php';
    require_once __DIR__.'/../includes/validator.php';
    // Skip error-handler to see actual errors
    require_once __DIR__.'/../includes/auth-v2.php';
    
    $results = [];
    
    // Test 1: Cache
    cache_set('test_key', ['hello' => 'world'], 60);
    $cached = cache_get('test_key');
    $results['cache_set_get'] = ($cached && $cached['hello'] === 'world') ? 'OK' : 'FAIL';
    cache_del('test_key');
    $results['cache_del'] = (cache_get('test_key') === null) ? 'OK' : 'FAIL';
    
    // Test 2: cache_remember
    $val = cache_remember('test_rem', function(){ return 42; }, 10);
    $results['cache_remember'] = ($val === 42) ? 'OK' : 'FAIL';
    cache_del('test_rem');
    
    // Test 3: Rate limiter
    rate_reset('test_rate');
    $r1 = rate_check('test_rate', 3, 60);
    $r2 = rate_check('test_rate', 3, 60);
    $r3 = rate_check('test_rate', 3, 60);
    $r4 = rate_check('test_rate', 3, 60);
    $results['rate_allow'] = ($r1 && $r2 && $r3) ? 'OK' : 'FAIL';
    $results['rate_block'] = (!$r4) ? 'OK' : 'FAIL';
    $results['rate_remaining'] = (rate_remaining('test_rate', 3, 60) === 0) ? 'OK' : 'FAIL';
    rate_reset('test_rate');
    
    // Test 4: Validator
    $e1 = validate(['email'=>'bad'], ['email'=>'required|email']);
    $results['validate_email_fail'] = isset($e1['email']) ? 'OK' : 'FAIL';
    $e2 = validate(['name'=>''], ['name'=>'required|min:2']);
    $results['validate_required'] = isset($e2['name']) ? 'OK' : 'FAIL';
    $e3 = validate(['email'=>'a@b.com', 'name'=>'Hi'], ['email'=>'required|email', 'name'=>'required|min:2']);
    $results['validate_pass'] = empty($e3) ? 'OK' : 'FAIL';
    
    // Test 5: Sanitize
    $dirty = '<script>alert(1)</script><b>OK</b>';
    $clean = sanitize_html($dirty);
    $results['sanitize'] = (strpos($clean, '<script>') === false) ? 'OK' : 'FAIL';
    
    // Test 6: Functions exist
    $results['fn_handle_upload'] = function_exists('handle_upload') ? 'OK' : 'FAIL';
    $results['fn_require_auth'] = function_exists('require_auth') ? 'OK' : 'FAIL';
    $results['fn_require_admin'] = function_exists('require_admin') ? 'OK' : 'FAIL';
    $results['fn_client_ip'] = function_exists('client_ip') ? 'OK' : 'FAIL';
    $results['fn_ss_log'] = function_exists('ss_log') ? 'OK' : 'FAIL — not loaded';
    
    // Test 7: Error logging (load manually)
    require_once __DIR__.'/../includes/error-handler.php';
    ss_log('info', 'Service test', ['file'=>__FILE__,'line'=>__LINE__]);
    $log = db()->fetchOne("SELECT id FROM error_logs WHERE message='Service test' ORDER BY id DESC LIMIT 1");
    $results['error_log_write'] = $log ? 'OK' : 'FAIL';
    if($log) db()->query("DELETE FROM error_logs WHERE id=?", [$log['id']]);
    
    $passed = count(array_filter($results, function($v){ return $v === 'OK'; }));
    echo json_encode(['passed'=>$passed, 'total'=>count($results), 'tests'=>$results], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    echo json_encode(['error'=>$e->getMessage(), 'file'=>$e->getFile(), 'line'=>$e->getLine()]);
}
