<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$results = [];
try { $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS edited_at DATETIME DEFAULT NULL"); $results[] = 'edited_at: OK'; } catch (Exception $e) { $results[] = 'edited_at: ' . $e->getMessage(); }
try { $pdo->exec("CREATE TABLE IF NOT EXISTS post_reports (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, user_id INT NOT NULL, reason VARCHAR(100) NOT NULL, detail TEXT, `status` ENUM('pending','reviewed','dismissed','actioned') DEFAULT 'pending', admin_note TEXT, reviewed_by INT, reviewed_at DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_post(post_id), INDEX idx_status(`status`), UNIQUE KEY unique_report(post_id,user_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $results[] = 'post_reports: OK'; } catch (Exception $e) { $results[] = 'post_reports: ' . $e->getMessage(); }
try { $pdo->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS read_at DATETIME DEFAULT NULL"); $results[] = 'read_at: OK'; } catch (Exception $e) { $results[] = 'read_at: ' . $e->getMessage(); }
echo json_encode(['success'=>true,'results'=>$results]);
