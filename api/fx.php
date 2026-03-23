<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pin_votes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pin_id INT NOT NULL,
        user_id INT NOT NULL,
        vote TINYINT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(pin_id, user_id),
        INDEX(pin_id)
    )");
    echo json_encode(['success'=>true,'msg'=>'pin_votes table OK']);
} catch (Throwable $e) { echo json_encode(['error'=>$e->getMessage()]); }
