<?php
header('Content-Type: application/json');
try {
    session_start();
    define('APP_ACCESS', true);
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    $d = db();
    $stories = $d->fetchAll(
        "SELECT s.*, u.fullname as user_name, u.avatar as user_avatar
         FROM stories s JOIN users u ON s.user_id = u.id
         WHERE s.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND s.`status` = 'active'
         ORDER BY s.created_at DESC LIMIT 50"
    );
    
    echo json_encode(['success' => true, 'data' => $stories ?: []]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'line' => $e->getLine()]);
}
