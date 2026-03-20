<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
header('Content-Type: application/json; charset=utf-8');
try {
    session_start();
    require_once __DIR__.'/../../includes/config.php';
    require_once __DIR__.'/../../includes/db.php';
    require_once __DIR__.'/../../includes/functions.php';
    require_once __DIR__.'/../../includes/auth-v2.php';
    
    $d=db();
    // Check table schemas
    $nc=$d->fetchAll("SHOW COLUMNS FROM notifications");
    $nrc=$d->fetchAll("SHOW COLUMNS FROM notification_reads");
    echo json_encode([
        'notif_cols'=>array_column($nc,'Field'),
        'reads_cols'=>array_column($nrc,'Field'),
    ]);
} catch(Throwable $e) {
    echo json_encode(['error'=>$e->getMessage(),'line'=>$e->getLine()]);
}
