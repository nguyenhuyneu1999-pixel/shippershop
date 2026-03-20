<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/cache.php';
require_once __DIR__.'/../includes/rate-limiter.php';
require_once __DIR__.'/../includes/validator.php';
require_once __DIR__.'/../includes/error-handler.php';
require_once __DIR__.'/../includes/auth-v2.php';
header('Content-Type: application/json');
$results = [];

// Test 1: Cache
cache_set('test_key', ['hello' => 'world'], 60);
$cached = cache_get('test_key');
$results['cache'] = ($cached && $cached['hello'] === 'world') ? 'OK' : 'FAIL';
cache_del('test_key');
$results['cache_del'] = (cache_get('test_key') === null) ? 'OK' : 'FAIL';

// Test 2: cache_remember
$val = cache_remember('test_remember', function(){ return 42; }, 10);
$results['cache_remember'] = ($val === 42) ? 'OK' : 'FAIL';
cache_del('test_remember');

// Test 3: Rate limiter
rate_reset('test_rate');
$r1 = rate_check('test_rate', 3, 60);
$r2 = rate_check('test_rate', 3, 60);
$r3 = rate_check('test_rate', 3, 60);
$r4 = rate_check('test_rate', 3, 60); // should be false
$results['rate_limit'] = ($r1 && $r2 && $r3 && !$r4) ? 'OK' : 'FAIL (r1='.(int)$r1.' r4='.(int)$r4.')';
$results['rate_remaining'] = (rate_remaining('test_rate', 3, 60) === 0) ? 'OK' : 'FAIL';
rate_reset('test_rate');

// Test 4: Validator
$errors = validate(['email'=>'bad', 'name'=>''], ['email'=>'required|email', 'name'=>'required|min:2']);
$results['validator_email'] = isset($errors['email']) ? 'OK' : 'FAIL';
$results['validator_required'] = isset($errors['name']) ? 'OK' : 'FAIL';
$clean = validate(['email'=>'test@test.com', 'name'=>'Huy'], ['email'=>'required|email', 'name'=>'required|min:2']);
$results['validator_pass'] = empty($clean) ? 'OK' : 'FAIL';

// Test 5: Sanitize
$dirty = '<script>alert("xss")</script><b>Hello</b>';
$clean = sanitize_html($dirty);
$results['sanitize'] = (strpos($clean, '<script>') === false && strpos($clean, 'Hello') !== false) ? 'OK' : 'FAIL';

// Test 6: Error handler
ss_log('info', 'Test log from fx.php', ['file'=>__FILE__, 'line'=>__LINE__]);
$log = db()->fetchOne("SELECT * FROM error_logs WHERE message='Test log from fx.php' ORDER BY id DESC LIMIT 1");
$results['error_log'] = $log ? 'OK' : 'FAIL';
if($log) db()->query("DELETE FROM error_logs WHERE id=?", [$log['id']]);

// Test 7: Auth functions exist
$results['auth_functions'] = (function_exists('require_auth') && function_exists('optional_auth') && function_exists('require_admin') && function_exists('client_ip')) ? 'OK' : 'FAIL';

// Test 8: Upload handler exists
$results['upload_handler'] = function_exists('handle_upload') ? 'OK' : 'FAIL';

// Summary
$passed = count(array_filter($results, function($v){ return $v === 'OK'; }));
$total = count($results);
echo json_encode(['tests' => $results, 'passed' => $passed, 'total' => $total, 'status' => $passed === $total ? 'ALL PASS' : 'SOME FAIL'], JSON_PRETTY_PRINT);
