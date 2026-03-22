<?php
/**
 * ShipperShop DB Export — Chạy trên shared hosting để export DB
 * URL: /api/db-export.php?key=ss_db_export_key
 * Kết quả: JSON chứa tất cả table structures + data
 */
if (($_GET['key'] ?? '') !== 'ss_db_export_key') {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid key']);
    exit;
}

set_time_limit(300);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$d = db();
$pdo = $d->getConnection();

$tables = [];
$result = $pdo->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tableName = $row[0];
    
    // Get CREATE TABLE
    $create = $pdo->query("SHOW CREATE TABLE `$tableName`")->fetch(PDO::FETCH_ASSOC);
    
    // Get row count
    $count = intval($pdo->query("SELECT COUNT(*) FROM `$tableName`")->fetchColumn());
    
    // Get data (limit 10000 rows per table for safety)
    $rows = [];
    if ($count > 0 && $count <= 10000) {
        $rows = $pdo->query("SELECT * FROM `$tableName`")->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($count > 10000) {
        $rows = $pdo->query("SELECT * FROM `$tableName` LIMIT 10000")->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $tables[] = [
        'name' => $tableName,
        'create_sql' => $create['Create Table'] ?? '',
        'row_count' => $count,
        'exported_rows' => count($rows),
        'data' => $rows
    ];
}

echo json_encode([
    'success' => true,
    'tables' => count($tables),
    'exported_at' => date('c'),
    'data' => $tables
], JSON_UNESCAPED_UNICODE);
