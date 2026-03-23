<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$r = [];

// Add compound index for bounding box queries
try {
    $pdo->exec("ALTER TABLE map_pins ADD INDEX idx_lat_lng (lat, lng)");
    $r[] = 'idx_lat_lng added';
} catch (Throwable $e) {
    $r[] = 'idx_lat_lng: ' . $e->getMessage();
}

// Add index on pin_type for filtered queries
try {
    $pdo->exec("ALTER TABLE map_pins ADD INDEX idx_type_lat_lng (pin_type, lat, lng)");
    $r[] = 'idx_type_lat_lng added';
} catch (Throwable $e) {
    $r[] = 'idx_type_lat_lng: ' . $e->getMessage();
}

// Add index on created_at for sorting
try {
    $pdo->exec("ALTER TABLE map_pins ADD INDEX idx_created (created_at)");
    $r[] = 'idx_created added';
} catch (Throwable $e) {
    $r[] = 'idx_created: ' . $e->getMessage();
}

echo json_encode(['success' => true, 'results' => $r]);
