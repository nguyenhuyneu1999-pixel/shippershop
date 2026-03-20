<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$results = [];

$tables = [
"CREATE TABLE IF NOT EXISTS post_reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  reason ENUM('spam','inappropriate','harassment','misinformation','other') NOT NULL DEFAULT 'other',
  detail TEXT,
  `status` ENUM('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
  reviewer_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  INDEX idx_pr_post (post_id),
  INDEX idx_pr_status (`status`),
  UNIQUE KEY unique_report (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS user_blocks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  blocked_user_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_block (user_id, blocked_user_id),
  INDEX idx_ub_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS search_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  query VARCHAR(255) NOT NULL,
  result_count INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sh_user (user_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS user_sessions (
  id VARCHAR(128) NOT NULL PRIMARY KEY,
  user_id INT NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  user_agent VARCHAR(500) DEFAULT NULL,
  last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_us_user (user_id),
  INDEX idx_us_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS email_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(255) NOT NULL,
  subject VARCHAR(500) NOT NULL,
  body TEXT NOT NULL,
  `status` ENUM('pending','sent','failed') DEFAULT 'pending',
  attempts INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME DEFAULT NULL,
  INDEX idx_eq_status (`status`, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS error_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level ENUM('debug','info','warning','error','critical') DEFAULT 'error',
  message TEXT,
  file VARCHAR(255) DEFAULT NULL,
  line INT DEFAULT NULL,
  trace TEXT,
  user_id INT DEFAULT NULL,
  url VARCHAR(500) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_el_level (level, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS page_views (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page VARCHAR(100) NOT NULL,
  user_id INT DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  referrer VARCHAR(500) DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pv_page (page, created_at),
  INDEX idx_pv_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS cron_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_name VARCHAR(100) NOT NULL,
  `status` ENUM('success','failed','running') DEFAULT 'running',
  duration_ms INT DEFAULT NULL,
  message TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_cl_job (job_name, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$names = ['post_reports','user_blocks','search_history','user_sessions','email_queue','error_logs','page_views','cron_logs'];
foreach ($tables as $i => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = ['table' => $names[$i], 'status' => 'OK'];
    } catch (PDOException $e) {
        $results[] = ['table' => $names[$i], 'status' => 'ERROR: '.$e->getMessage()];
    }
}

// Verify all tables exist
$allTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$total = count($allTables);

echo json_encode(['step' => 'new_tables', 'results' => $results, 'total_tables' => $total], JSON_PRETTY_PRINT);
