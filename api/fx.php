<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$results = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        created_by INT NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        location VARCHAR(200),
        event_date DATETIME NOT NULL,
        `status` ENUM('active','cancelled','completed') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(group_id), INDEX(event_date)
    )");
    $results[] = 'events table OK';
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS event_rsvps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        `status` ENUM('going','interested','not_going') DEFAULT 'going',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY(event_id, user_id), INDEX(event_id)
    )");
    $results[] = 'event_rsvps table OK';
} catch (Throwable $e) { $results[] = $e->getMessage(); }
echo json_encode(['success' => true, 'results' => $results]);
