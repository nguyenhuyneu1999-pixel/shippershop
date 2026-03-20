<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo=db()->getConnection();

// Check constraints
$indexes=$pdo->query("SHOW INDEX FROM conversations WHERE Key_name='unique_pair'")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['indexes'=>$indexes], JSON_PRETTY_PRINT);
