<?php
/**
 * ShipperShop DB Import — Chạy trên VPS để import DB từ file export
 * Upload file export.json lên VPS, rồi chạy:
 * php /var/www/shippershop/public_html/api/db-import.php /path/to/export.json
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

$file = $argv[1] ?? '';
if (!$file || !file_exists($file)) {
    die("Usage: php db-import.php /path/to/export.json\n");
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "Loading export file...\n";
$json = json_decode(file_get_contents($file), true);
if (!$json || !isset($json['data'])) {
    die("Invalid export file\n");
}

$d = db();
$pdo = $d->getConnection();

echo "Found " . count($json['data']) . " tables\n\n";

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

foreach ($json['data'] as $table) {
    $name = $table['name'];
    echo "Table: $name ({$table['row_count']} rows)... ";
    
    // Drop and recreate
    $pdo->exec("DROP TABLE IF EXISTS `$name`");
    $pdo->exec($table['create_sql']);
    
    // Insert data
    $inserted = 0;
    foreach ($table['data'] as $row) {
        $cols = array_keys($row);
        $placeholders = implode(',', array_fill(0, count($cols), '?'));
        $colStr = implode(',', array_map(function($c) { return "`$c`"; }, $cols));
        try {
            $stmt = $pdo->prepare("INSERT INTO `$name` ($colStr) VALUES ($placeholders)");
            $stmt->execute(array_values($row));
            $inserted++;
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
    echo "$inserted inserted\n";
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "\n✅ Import complete!\n";
