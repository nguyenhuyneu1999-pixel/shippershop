<?php
/**
 * ShipperShop Health Check — Public endpoint
 */
require_once __DIR__.'/../../includes/db.php';
header('Content-Type: application/json');

$checks=[];$start=microtime(true);

// DB
try{$r=db()->fetchOne("SELECT 1 as ok");$checks['db']=$r?'OK':'FAIL';}catch(\Throwable $e){$checks['db']='FAIL: '.$e->getMessage();}

// Disk
$free=function_exists('disk_free_space')?disk_free_space('/'):0;
$checks['disk_free_gb']=round($free/1024/1024/1024,2);
$checks['disk_ok']=$free>100*1024*1024?'OK':'LOW';

// PHP
$checks['php_version']=PHP_VERSION;

// Tables
try{$t=db()->fetchOne("SELECT COUNT(*) as c FROM information_schema.tables WHERE table_schema=DATABASE()");$checks['tables']=intval($t['c']);}catch(\Throwable $e){$checks['tables']='FAIL';}

// Response time
$checks['response_ms']=round((microtime(true)-$start)*1000,2);
$checks['timestamp']=date('Y-m-d H:i:s');
$checks['status']='healthy';

echo json_encode($checks,JSON_PRETTY_PRINT);
