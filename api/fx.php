<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];
$sqls = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS deleted_at DATETIME DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS delete_reason VARCHAR(500) DEFAULT NULL",
    "ALTER TABLE marketplace_listings MODIFY COLUMN `status` ENUM('active','sold','deleted','pending') DEFAULT 'active'",
];
foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 60); }
}
echo json_encode(['success' => true, 'results' => $r]);
