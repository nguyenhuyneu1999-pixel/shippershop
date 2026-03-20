<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Drop unique_pair constraint (code already prevents duplicate private convs)
try {
    $pdo->exec("ALTER TABLE conversations DROP INDEX unique_pair");
    echo json_encode(['status'=>'dropped unique_pair']);
} catch(PDOException $e) {
    echo json_encode(['status'=>'already dropped or error: '.$e->getMessage()]);
}
