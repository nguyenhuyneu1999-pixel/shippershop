<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];
$sqls = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS notification_email TINYINT DEFAULT 1",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS notification_push TINYINT DEFAULT 1",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS privacy_profile ENUM('public','private') DEFAULT 'public'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS privacy_online TINYINT DEFAULT 1",
    "ALTER TABLE group_posts ADD COLUMN IF NOT EXISTS is_pinned TINYINT DEFAULT 0",
];
foreach ($sqls as $sql) {
    try { $pdo->exec($sql); $r[] = 'OK'; } catch (Exception $e) { $r[] = substr($e->getMessage(), 0, 50); }
}
echo json_encode(['success' => true, 'results' => $r]);
