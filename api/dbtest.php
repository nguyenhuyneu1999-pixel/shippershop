<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

// Create payos_payments table
try {
    $d->query("CREATE TABLE IF NOT EXISTS payos_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_code BIGINT NOT NULL UNIQUE,
        plan_id INT DEFAULT 0,
        type VARCHAR(20) NOT NULL DEFAULT 'subscription',
        amount INT NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        checkout_url VARCHAR(500),
        payment_link_id VARCHAR(100),
        paid_at DATETIME DEFAULT NULL,
        raw_webhook TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_status (`status`),
        INDEX idx_order (order_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Created payos_payments table\n";
} catch(Throwable $e) {
    echo "Table: ".$e->getMessage()."\n";
}

// Verify
$cols=$d->fetchAll("SHOW COLUMNS FROM payos_payments");
echo "\nColumns:\n";
foreach($cols as $c) echo "  ".$c['Field']." | ".$c['Type']."\n";

echo "\nDONE\n";
