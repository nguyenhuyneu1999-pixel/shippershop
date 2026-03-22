<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$results = [];

// Ensure all columns exist
$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS `status` ENUM('active','banned','suspended') DEFAULT 'active'",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'user'",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS report_count INT DEFAULT 0",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_draft TINYINT DEFAULT 0",
];

foreach ($migrations as $sql) {
    try {
        $pdo->exec($sql);
        $results[] = 'OK';
    } catch (Exception $e) {
        $results[] = substr($e->getMessage(), 0, 60);
    }
}

// Count tables
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$results[] = 'tables: ' . count($tables);

echo json_encode(['success' => true, 'migrations' => count($migrations), 'results' => $results]);
