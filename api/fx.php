<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];
$sqls = [
    "ALTER TABLE wishlists ADD COLUMN IF NOT EXISTS listing_id INT DEFAULT NULL",
    "ALTER TABLE wishlists ADD INDEX IF NOT EXISTS idx_wish_listing (user_id, listing_id)",
];
foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 50); }
}
echo json_encode(['success' => true, 'results' => $r]);
