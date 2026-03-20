<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$rows = db()->fetchAll("SELECT DISTINCT province FROM posts WHERE province IS NOT NULL AND province != '' ORDER BY province");
echo json_encode($rows);
