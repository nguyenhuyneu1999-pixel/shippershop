<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

try {
    $d = db();
    $stories = $d->fetchAll("SELECT * FROM stories LIMIT 1");
    echo json_encode(['ok' => true, 'count' => count($stories ?: [])]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => basename($e->getFile())]);
}
