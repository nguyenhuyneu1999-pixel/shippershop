<?php
error_reporting(E_ALL);
ini_set("display_errors",1);
header("Content-Type: text/plain");

echo "START\n";

require_once __DIR__ . '/../includes/db.php';
$d = db();

echo "DB OK\n";

// Check posts table columns
$cols = $d->fetchAll("SHOW COLUMNS FROM posts");
$colNames = array_column($cols, 'Field');
echo "Columns: " . implode(', ', $colNames) . "\n\n";

// Simple test insert
try {
    $d->query("INSERT INTO posts (user_id, content, image, type, province, district, likes_count, comments_count, shares_count, `status`, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())", [
        3,
        "Test real post - tiết kiệm xăng cho shipper",
        "uploads/posts/real/real_1.jpg",
        "tips",
        "Hồ Chí Minh",
        "Tân Bình",
        25, 8, 3
    ]);
    echo "✅ Test insert OK\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
