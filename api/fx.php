<?php
error_reporting(E_ALL); ini_set('display_errors',1);
header('Content-Type: application/json');
try {
    require_once __DIR__.'/../includes/db.php';
    require_once __DIR__.'/../includes/cache.php';
    require_once __DIR__.'/../includes/rate-limiter.php';
    require_once __DIR__.'/../includes/validator.php';
    require_once __DIR__.'/../includes/auth-v2.php';
    $R = [];

    // Cache
    cache_set('t1', ['a'=>1], 60); $R['cache_set'] = (cache_get('t1')['a']===1)?'OK':'FAIL';
    cache_del('t1'); $R['cache_del'] = (cache_get('t1')===null)?'OK':'FAIL';
    $R['cache_remember'] = (cache_remember('t2',function(){return 99;},10)===99)?'OK':'FAIL'; cache_del('t2');

    // Rate limiter (new signature: endpoint, ip, max, window)
    rate_reset('test_ep','127.0.0.1');
    $a=rate_check('test_ep','127.0.0.1',3,60);
    $b=rate_check('test_ep','127.0.0.1',3,60);
    $c=rate_check('test_ep','127.0.0.1',3,60);
    $d2=rate_check('test_ep','127.0.0.1',3,60);
    $R['rate_allow'] = ($a&&$b&&$c)?'OK':'FAIL';
    $R['rate_block'] = (!$d2)?'OK':'FAIL';
    $R['rate_remaining'] = (rate_remaining('test_ep','127.0.0.1',3,60)===0)?'OK':'FAIL';
    rate_reset('test_ep','127.0.0.1');

    // Validator
    $e1=validate(['email'=>'bad'],['email'=>'required|email']);
    $R['val_email_fail'] = isset($e1['email'])?'OK':'FAIL';
    $e2=validate(['n'=>''],['n'=>'required']);
    $R['val_required'] = isset($e2['n'])?'OK':'FAIL';
    $e3=validate(['email'=>'a@b.com','n'=>'Hi'],['email'=>'required|email','n'=>'required|min:2']);
    $R['val_pass'] = empty($e3)?'OK':'FAIL';
    $R['sanitize'] = (strpos(sanitize_html('<script>x</script>'),'<script>')===false)?'OK':'FAIL';

    // Functions exist
    $R['fn_upload'] = function_exists('handle_upload')?'OK':'FAIL';
    require_once __DIR__.'/../includes/upload-handler.php';
    $R['fn_upload2'] = function_exists('handle_upload')?'OK':'FAIL';
    $R['fn_auth'] = (function_exists('require_auth')&&function_exists('require_admin'))?'OK':'FAIL';
    $R['fn_ip'] = function_exists('client_ip')?'OK':'FAIL';

    // Error handler
    require_once __DIR__.'/../includes/error-handler.php';
    ss_log('info','svc_test',[]);
    $log=db()->fetchOne("SELECT id FROM error_logs WHERE message='svc_test' ORDER BY id DESC LIMIT 1");
    $R['error_log'] = $log?'OK':'FAIL';
    if($log) db()->query("DELETE FROM error_logs WHERE id=?",[$log['id']]);

    $ok=count(array_filter($R,function($v){return $v==='OK';}));
    echo json_encode(['passed'=>$ok,'total'=>count($R),'status'=>$ok===count($R)?'ALL PASS':'FAIL','tests'=>$R],JSON_PRETTY_PRINT);
} catch(Throwable $e) {
    echo json_encode(['error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
}
