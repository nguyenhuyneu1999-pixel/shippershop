<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
$pdo = db()->getConnection();
header('Content-Type: text/plain');
$r = [];

// 1. group_categories table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `group_categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `icon` VARCHAR(50) DEFAULT '📁',
        `parent_id` INT DEFAULT NULL,
        `sort_order` INT DEFAULT 0,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_parent` (`parent_id`),
        INDEX `idx_sort` (`sort_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r[] = "OK group_categories table";
} catch (Throwable $e) { $r[] = "ERR group_categories: " . $e->getMessage(); }

// 2. group_rules table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `group_rules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `group_id` INT NOT NULL,
        `rule_order` INT NOT NULL DEFAULT 1,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT DEFAULT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_group` (`group_id`, `rule_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r[] = "OK group_rules table";
} catch (Throwable $e) { $r[] = "ERR group_rules: " . $e->getMessage(); }

// 3. Add columns to groups if missing
$cols = array_column($pdo->query("SHOW COLUMNS FROM `groups`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
$adds = [
    ['weekly_active', "INT DEFAULT 0"],
    ['category_id', "INT DEFAULT NULL"],
    ['banner_color', "VARCHAR(20) DEFAULT '#EE4D2D'"],
];
foreach ($adds as $a) {
    if (!in_array($a[0], $cols)) {
        try {
            $pdo->exec("ALTER TABLE `groups` ADD COLUMN `{$a[0]}` {$a[1]}");
            $r[] = "OK added groups.{$a[0]}";
        } catch (Throwable $e) { $r[] = "ERR groups.{$a[0]}: " . $e->getMessage(); }
    } else { $r[] = "SKIP groups.{$a[0]}"; }
}

// 4. Add columns to group_posts if missing
$pcols = array_column($pdo->query("SHOW COLUMNS FROM group_posts")->fetchAll(PDO::FETCH_ASSOC), 'Field');
$padds = [
    ['title', "VARCHAR(500) DEFAULT NULL AFTER `content`"],
    ['shares_count', "INT DEFAULT 0"],
];
foreach ($padds as $a) {
    if (!in_array($a[0], $pcols)) {
        try {
            $pdo->exec("ALTER TABLE group_posts ADD COLUMN `{$a[0]}` {$a[1]}");
            $r[] = "OK added group_posts.{$a[0]}";
        } catch (Throwable $e) { $r[] = "ERR group_posts.{$a[0]}: " . $e->getMessage(); }
    } else { $r[] = "SKIP group_posts.{$a[0]}"; }
}

// 5. group_post_likes table for leaderboard
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `group_post_likes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `uniq_like` (`post_id`, `user_id`),
        INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r[] = "OK group_post_likes table";
} catch (Throwable $e) { $r[] = "ERR group_post_likes: " . $e->getMessage(); }

// 6. group_post_comments table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `group_post_comments` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `parent_id` INT DEFAULT NULL,
        `content` TEXT NOT NULL,
        `likes_count` INT DEFAULT 0,
        `status` ENUM('active','removed') DEFAULT 'active',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_post` (`post_id`, `status`, `created_at`),
        INDEX `idx_user` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $r[] = "OK group_post_comments table";
} catch (Throwable $e) { $r[] = "ERR group_post_comments: " . $e->getMessage(); }

// 7. Indexes on groups
try { $pdo->exec("ALTER TABLE `groups` ADD INDEX idx_cat (`category_id`, `status`)"); $r[] = "OK idx_cat"; } catch(Throwable $e) { $r[] = "SKIP idx_cat"; }
try { $pdo->exec("ALTER TABLE `groups` ADD INDEX idx_popular (`status`, `member_count` DESC)"); $r[] = "OK idx_popular"; } catch(Throwable $e) { $r[] = "SKIP idx_popular"; }

// ============================================
// 8. SEED CATEGORIES
// ============================================
try {
    $pdo->exec("INSERT IGNORE INTO group_categories (id,name,slug,icon,parent_id,sort_order) VALUES
        (1,'Hãng vận chuyển','hang-van-chuyen','🚚',NULL,1),
        (2,'Khu vực','khu-vuc','📍',NULL,2),
        (3,'Kinh nghiệm','kinh-nghiem','💡',NULL,3),
        (4,'Đánh giá','danh-gia','⭐',NULL,4),
        (5,'Confession','confession','💬',NULL,5),
        (6,'Hỗ trợ','ho-tro','🆘',NULL,6),
        (7,'Mẹo & Thủ thuật','meo-thu-thuat','🔧',NULL,7),
        (8,'Mua bán','mua-ban','🛒',NULL,8),
        -- Sub-categories
        (10,'GHTK',     'ghtk',     '🟢',1,1),
        (11,'GHN',      'ghn',      '🟠',1,2),
        (12,'J&T',      'jt',       '🔴',1,3),
        (13,'SPX',      'spx',      '🔴',1,4),
        (14,'Ninja Van','ninja-van','🔴',1,5),
        (15,'Viettel Post','viettel-post','🔴',1,6),
        (16,'Grab/Be/Gojek','grab-be-gojek','🟢',1,7),
        (20,'Hà Nội',   'ha-noi',   '🏙️',2,1),
        (21,'HCM',      'hcm',      '🏙️',2,2),
        (22,'Đà Nẵng',  'da-nang',  '🏙️',2,3),
        (23,'Cần Thơ',  'can-tho',  '🏙️',2,4)
    ");
    $r[] = "OK seed categories";
} catch (Throwable $e) { $r[] = "ERR categories seed: " . $e->getMessage(); }

// 9. Update existing groups with category_id
try {
    $pdo->exec("UPDATE `groups` SET category_id=1 WHERE category='shipping'");
    $pdo->exec("UPDATE `groups` SET category_id=2 WHERE category='location'");
    $pdo->exec("UPDATE `groups` SET category_id=4 WHERE category='review'");
    $pdo->exec("UPDATE `groups` SET category_id=5 WHERE category='confession'");
    $pdo->exec("UPDATE `groups` SET category_id=7 WHERE category='tips'");
    $r[] = "OK updated existing groups category_id";
} catch (Throwable $e) { $r[] = "ERR update cats: " . $e->getMessage(); }

// 10. Seed rules for group #1
try {
    $pdo->exec("INSERT IGNORE INTO group_rules (id,group_id,rule_order,title,description) VALUES
        (1,1,1,'Chỉ đăng nội dung liên quan GHTK','Bài viết phải liên quan đến Giao Hàng Tiết Kiệm, không spam quảng cáo'),
        (2,1,2,'Tôn trọng mọi người','Không xúc phạm, chửi bới, phân biệt vùng miền'),
        (3,1,3,'Không đăng thông tin khách hàng','Tuyệt đối không chia sẻ SĐT, địa chỉ, đơn hàng của khách'),
        (4,1,4,'Không spam link kiếm tiền','Link ref, app kiếm tiền sẽ bị xóa và ban'),
        (5,1,5,'Chia sẻ mẹo hay được khuyến khích','Đăng kinh nghiệm, mẹo giao hàng sẽ được ưu tiên'),
        (6,2,1,'Nội dung liên quan Grab/Be/Gojek','Chỉ đăng nội dung liên quan dịch vụ giao hàng công nghệ'),
        (7,2,2,'Tôn trọng mọi người','Không chửi bới, phân biệt'),
        (8,2,3,'Không đăng tin tuyển dụng spam','Tuyển dụng chỉ được đăng cuối tuần'),
        (9,3,1,'Chỉ shipper khu vực Sài Gòn','Nhóm dành cho shipper hoạt động tại TP.HCM'),
        (10,3,2,'Chia sẻ tuyến đường, mẹo giao hàng','Khuyến khích chia sẻ kinh nghiệm di chuyển'),
        (11,3,3,'Cảnh báo giao thông','Thông tin kẹt xe, ngập nước rất hữu ích')
    ");
    $r[] = "OK seed rules";
} catch (Throwable $e) { $r[] = "ERR rules: " . $e->getMessage(); }

// 11. Create 4 new test groups (1 per category type)
try {
    $pdo->exec("INSERT IGNORE INTO `groups` (id,name,slug,description,creator_id,category,category_id,member_count,post_count,icon_image,banner_color) VALUES
        (9,'Tips Giao Hàng Nhanh','tips-giao-hang-nhanh','Chia sẻ mẹo giao hàng nhanh chóng, tiết kiệm thời gian và xăng cho shipper',2,'tips',3,432,87,NULL,'#2196F3'),
        (10,'Hỏi Đáp Shipper','hoi-dap-shipper','Nơi giải đáp mọi thắc mắc về nghề shipper: luật giao thông, bảo hiểm, thuế...',2,'support',6,678,234,NULL,'#4CAF50'),
        (11,'Shipper Đà Nẵng','shipper-da-nang','Cộng đồng shipper khu vực Đà Nẵng - Quảng Nam - Huế',2,'location',2,345,98,NULL,'#FF9800'),
        (12,'Đồ Nghề Shipper','do-nghe-shipper','Review và mua bán đồ nghề: túi giữ nhiệt, điện thoại, bao tay, áo mưa...',2,'review',8,567,156,NULL,'#9C27B0')
    ");
    $r[] = "OK seed 4 new groups";
    
    // Add admin as member
    foreach ([9,10,11,12] as $gid) {
        try { $pdo->exec("INSERT IGNORE INTO group_members (group_id,user_id,role) VALUES ($gid,2,'admin')"); } catch(Throwable $e) {}
    }
    // Add user#3 as test member
    foreach ([1,3,6,9,10] as $gid) {
        try { $pdo->exec("INSERT IGNORE INTO group_members (group_id,user_id,role) VALUES ($gid,3,'member')"); } catch(Throwable $e) {}
    }
    $r[] = "OK seed members";
} catch (Throwable $e) { $r[] = "ERR seed groups: " . $e->getMessage(); }

// 12. Seed 1 post per new group
try {
    $pdo->exec("INSERT IGNORE INTO group_posts (id,group_id,user_id,content,type,likes_count,comments_count) VALUES
        (1,9,2,'Mẹo số 1: Luôn kiểm tra địa chỉ trên Google Maps trước khi xuất phát. Nhiều địa chỉ sai số nhà, nếu gọi trước cho khách sẽ tiết kiệm rất nhiều thời gian. Đặc biệt các đơn giao buổi tối, nên gọi trước 30 phút!','post',15,3),
        (2,10,3,'Mình mới đăng ký shipper GHTK được 1 tuần. Xin hỏi mọi người là khi giao hàng COD mà khách không nhận thì mình xử lý thế nào ạ? Có bị trừ tiền không?','post',23,8),
        (3,11,2,'Các bạn shipper Đà Nẵng chú ý: Đường Nguyễn Văn Linh đang sửa đoạn gần Big C, nên đi vòng qua Võ Văn Kiệt để tránh kẹt xe nhé!','post',31,5),
        (4,12,2,'Review túi giữ nhiệt Grab loại 45L: Dùng được 6 tháng rồi, khá bền. Giữ nhiệt tốt khoảng 2 tiếng. Giá 180k trên Shopee. Điểm trừ là quai đeo hơi ngắn cho người cao trên 1m7.','post',42,12)
    ");
    $r[] = "OK seed posts";
} catch (Throwable $e) { $r[] = "ERR seed posts: " . $e->getMessage(); }

// 13. Seed rules for new groups
try {
    $pdo->exec("INSERT IGNORE INTO group_rules (group_id,rule_order,title,description) VALUES
        (9,1,'Chia sẻ mẹo thực tế','Chỉ đăng mẹo đã áp dụng thực tế, không copy từ internet'),
        (9,2,'Tôn trọng người khác','Không chê bai cách làm của người khác'),
        (10,1,'Đặt câu hỏi rõ ràng','Mô tả tình huống cụ thể để được tư vấn chính xác'),
        (10,2,'Không tư vấn sai luật','Không khuyên người khác làm trái quy định'),
        (11,1,'Chỉ shipper Đà Nẵng','Nội dung liên quan khu vực Đà Nẵng'),
        (12,1,'Review trung thực','Đánh giá thật, có ảnh càng tốt'),
        (12,2,'Ghi rõ giá và nơi mua','Giúp anh em dễ tham khảo')
    ");
    $r[] = "OK seed new rules";
} catch (Throwable $e) { $r[] = "ERR new rules: " . $e->getMessage(); }

echo implode("\n", $r);
