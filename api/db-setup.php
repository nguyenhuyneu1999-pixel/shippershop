<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();
try{
    $d->query("CREATE TABLE IF NOT EXISTS payos_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        plan_id INT NOT NULL,
        order_code BIGINT NOT NULL UNIQUE,
        amount INT NOT NULL,
        checkout_url VARCHAR(500),
        qr_code TEXT,
        `status` ENUM('pending','completed','failed','cancelled') DEFAULT 'pending',
        payos_data TEXT,
        paid_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_order (order_code),
        INDEX idx_status (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table payos_payments created\n";
}catch(Throwable $e){echo "Table: ".$e->getMessage()."\n";}

// Verify
$cols=$d->fetchAll("SHOW COLUMNS FROM payos_payments");
foreach($cols as $c) echo "  ".$c['Field']." | ".$c['Type']."\n";
echo "DONE\n";
