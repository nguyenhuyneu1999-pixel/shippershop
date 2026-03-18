<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db()->getConnection();
$r = [];

// 1. Add columns to conversations for group support
$cols = array_column($pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_ASSOC), 'Field');
$adds = [
    ['type', "ENUM('private','group') DEFAULT 'private' AFTER `id`"],
    ['name', "VARCHAR(200) DEFAULT NULL AFTER `type`"],
    ['avatar', "VARCHAR(500) DEFAULT NULL AFTER `name`"],
    ['creator_id', "INT DEFAULT NULL AFTER `avatar`"],
    ['invite_link', "VARCHAR(100) DEFAULT NULL"],
    ['description', "TEXT DEFAULT NULL"],
    ['is_pinned', "TINYINT(1) DEFAULT 0"],
    ['is_muted', "TINYINT(1) DEFAULT 0"],
];
foreach ($adds as $a) {
    if (!in_array($a[0], $cols)) {
        try { $pdo->exec("ALTER TABLE conversations ADD COLUMN `{$a[0]}` {$a[1]}"); $r[] = "OK conversations.{$a[0]}"; }
        catch (Throwable $e) { $r[] = "ERR conversations.{$a[0]}: " . $e->getMessage(); }
    } else { $r[] = "SKIP conversations.{$a[0]}"; }
}

// 2. Create conversation_members table (for group chats)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `conversation_members` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `conversation_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `role` ENUM('admin','member') DEFAULT 'member',
        `nickname` VARCHAR(100) DEFAULT NULL,
        `is_muted` TINYINT(1) DEFAULT 0,
        `joined_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_member` (`conversation_id`, `user_id`),
        INDEX `idx_user` (`user_id`),
        INDEX `idx_conv` (`conversation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r[] = "OK conversation_members table";
} catch (Throwable $e) { $r[] = "ERR conversation_members: " . $e->getMessage(); }

// 3. Add pinned_messages table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pinned_messages` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `conversation_id` INT NOT NULL,
        `message_id` INT NOT NULL,
        `pinned_by` INT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_pin` (`conversation_id`, `message_id`),
        INDEX `idx_conv` (`conversation_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r[] = "OK pinned_messages table";
} catch (Throwable $e) { $r[] = "ERR pinned_messages: " . $e->getMessage(); }

// 4. Add is_pinned to messages
$mcols = array_column($pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_ASSOC), 'Field');
if (!in_array('is_pinned', $mcols)) {
    try { $pdo->exec("ALTER TABLE messages ADD COLUMN `is_pinned` TINYINT(1) DEFAULT 0"); $r[] = "OK messages.is_pinned"; }
    catch (Throwable $e) { $r[] = "ERR messages.is_pinned: " . $e->getMessage(); }
} else { $r[] = "SKIP messages.is_pinned"; }

// 5. Seed 3 test group conversations
try {
    // Group 1: Điều hành bill
    $pdo->exec("INSERT IGNORE INTO conversations (id, type, name, creator_id, last_message, last_message_at, `status`, description) VALUES 
        (100, 'group', 'Shipper GHTK Hà Nội', 2, 'Anh em check đơn chiều nay nhé', NOW(), 'active', 'Nhóm điều phối đơn GHTK khu vực Hà Nội'),
        (101, 'group', 'Team Ship Quận 1-3', 2, 'Kẹt xe Nguyễn Huệ, đi vòng nhé', NOW(), 'active', 'Nhóm shipper khu vực Quận 1, 2, 3 HCM'),
        (102, 'group', 'Hội Shipper Đà Nẵng', 2, 'Mai có đơn Sơn Trà ai nhận?', NOW(), 'active', 'Chia sẻ đơn, hỗ trợ giao hàng Đà Nẵng')
    ");
    $r[] = "OK seed group conversations";
    
    // Add members to groups
    $members = [
        [100, 2, 'admin'], [100, 3, 'member'], [100, 6, 'member'], [100, 10, 'member'], [100, 15, 'member'],
        [101, 2, 'admin'], [101, 8, 'member'], [101, 12, 'member'], [101, 20, 'member'],
        [102, 2, 'admin'], [102, 3, 'member'], [102, 25, 'member'], [102, 30, 'member'], [102, 35, 'member'], [102, 40, 'member'],
    ];
    foreach ($members as $m) {
        try { $pdo->exec("INSERT IGNORE INTO conversation_members (conversation_id, user_id, role) VALUES ({$m[0]}, {$m[1]}, '{$m[2]}')"); } catch(Throwable $e) {}
    }
    $r[] = "OK seed group members";
    
    // Add messages to groups
    $pdo->exec("INSERT IGNORE INTO messages (id, conversation_id, sender_id, content, type, is_read, created_at) VALUES
        (100, 100, 2, 'Anh em check đơn chiều nay nhé, có 15 đơn cần giao trước 5h', 'text', 1, NOW() - INTERVAL 30 MINUTE),
        (101, 100, 3, 'Ok anh, em nhận 5 đơn khu Cầu Giấy', 'text', 1, NOW() - INTERVAL 25 MINUTE),
        (102, 100, 6, 'Em lấy 3 đơn Đống Đa ạ', 'text', 0, NOW() - INTERVAL 20 MINUTE),
        (103, 101, 2, 'Kẹt xe Nguyễn Huệ, đi vòng Hai Bà Trưng nhé anh em', 'text', 1, NOW() - INTERVAL 15 MINUTE),
        (104, 101, 8, 'Cảm ơn anh, suýt chui vô kẹt', 'text', 0, NOW() - INTERVAL 10 MINUTE),
        (105, 102, 2, 'Mai có đơn Sơn Trà ai nhận? 3 đơn, ship phí 25k/đơn', 'text', 1, NOW() - INTERVAL 5 MINUTE),
        (106, 102, 25, 'Em nhận ạ, sáng mai em qua lấy', 'text', 0, NOW() - INTERVAL 2 MINUTE)
    ");
    $r[] = "OK seed group messages";
} catch (Throwable $e) { $r[] = "ERR seed groups: " . $e->getMessage(); }

// 6. Generate invite links
foreach ([100, 101, 102] as $cid) {
    $link = substr(md5("ss_group_" . $cid . time()), 0, 12);
    try { $pdo->exec("UPDATE conversations SET invite_link = '$link' WHERE id = $cid"); } catch(Throwable $e) {}
}
$r[] = "OK invite links";

echo implode("\n", $r);
