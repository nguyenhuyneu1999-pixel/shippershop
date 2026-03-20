<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$results = [];

$columns = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS total_success INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS total_posts INT DEFAULT 0",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS settings JSON DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified_at DATETIME DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_until DATETIME DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS ban_reason VARCHAR(500) DEFAULT NULL",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) DEFAULT 0",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS edited_at DATETIME DEFAULT NULL",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS report_count INT DEFAULT 0",
    "ALTER TABLE posts ADD COLUMN IF NOT EXISTS view_count INT DEFAULT 0",
    "ALTER TABLE comments ADD COLUMN IF NOT EXISTS edited_at DATETIME DEFAULT NULL",
];

foreach ($columns as $sql) {
    try {
        $pdo->exec($sql);
        preg_match('/TABLE (\w+).*?EXISTS (\w+)/', $sql, $m);
        $results[] = ['column' => ($m[1]??'?').'.'.($m[2]??'?'), 'status' => 'OK'];
    } catch (PDOException $e) {
        $results[] = ['sql' => substr($sql, 0, 60), 'status' => 'ERROR: '.$e->getMessage()];
    }
}

echo json_encode(['step' => 'columns', 'results' => $results], JSON_PRETTY_PRINT);
