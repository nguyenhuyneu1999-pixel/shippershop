<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
header('Content-Type: application/json; charset=utf-8');

try {
    session_start();
    require_once __DIR__.'/../../includes/config.php';
    require_once __DIR__.'/../../includes/db.php';
    require_once __DIR__.'/../../includes/functions.php';
    
    // Test if auth-v2 causes the crash
    require_once __DIR__.'/../../includes/auth-v2.php';
    
    echo json_encode(['step'=>'auth-v2 loaded','ok'=>true]);
} catch(Throwable $e) {
    echo json_encode(['error'=>$e->getMessage(),'file'=>basename($e->getFile()),'line'=>$e->getLine()]);
}
