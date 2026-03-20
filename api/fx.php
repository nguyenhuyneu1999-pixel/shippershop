<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$cols = db()->fetchAll("SHOW COLUMNS FROM users");
echo json_encode(array_column($cols, 'Field'));
