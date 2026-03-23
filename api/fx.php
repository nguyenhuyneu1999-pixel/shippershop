<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$d = db();

// Check stories
$stories = $d->fetchAll("SELECT * FROM stories LIMIT 5");

// Check if JOIN works without subquery
try {
    $joined = $d->fetchAll("SELECT s.id, s.user_id, s.content, u.fullname FROM stories s JOIN users u ON s.user_id = u.id LIMIT 5");
    echo json_encode(['stories' => $stories, 'joined' => $joined]);
} catch (Throwable $e) {
    echo json_encode(['stories' => $stories, 'join_error' => $e->getMessage()]);
}
