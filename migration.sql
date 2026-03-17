CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(200) UNIQUE NOT NULL,
  `description` TEXT,
  `rules` TEXT,
  `cover_image` VARCHAR(500),
  `icon_image` VARCHAR(500),
  `creator_id` INT NOT NULL,
  `category` VARCHAR(100) DEFAULT 'general',
  `member_count` INT DEFAULT 1,
  `post_count` INT DEFAULT 0,
  `privacy` ENUM('public','private') DEFAULT 'public',
  `status` ENUM('active','suspended') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `group_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `role` ENUM('admin','moderator','member') DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_group_user` (`group_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `group_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `content` TEXT NOT NULL,
  `images` TEXT,
  `type` VARCHAR(50) DEFAULT 'post',
  `likes_count` INT DEFAULT 0,
  `comments_count` INT DEFAULT 0,
  `status` ENUM('active','pending','removed') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_group` (`group_id`, `status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `friends` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `friend_id` INT NOT NULL,
  `status` ENUM('pending','accepted','rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_friend` (`user_id`, `friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `users` ADD COLUMN `last_active` TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE `users` ADD COLUMN `is_online` TINYINT(1) DEFAULT 0;
ALTER TABLE `users` ADD COLUMN `cover_image` VARCHAR(500) NULL DEFAULT NULL;

INSERT INTO `groups` (`name`, `slug`, `description`, `rules`, `creator_id`, `category`, `member_count`, `post_count`) VALUES
('Shipper GHTK', 'shipper-ghtk', 'Cộng đồng shipper Giao Hàng Tiết Kiệm', '1. Tôn trọng\n2. Không spam', 1, 'shipping', 856, 234),
('Shipper Grab - Be - Gojek', 'shipper-grab-be-gojek', 'Tài xế công nghệ thảo luận quest, thưởng, tips', '1. Không chửi bới\n2. Hỗ trợ newbie', 1, 'shipping', 1243, 567),
('Shipper Sài Gòn', 'shipper-sai-gon', 'Hội shipper khu vực TP.HCM', '1. Khu vực HCM\n2. Chia sẻ giao thông', 1, 'location', 2100, 891),
('Shipper Hà Nội', 'shipper-ha-noi', 'Cộng đồng shipper thủ đô', '1. Khu vực HN\n2. Giúp đỡ nhau', 1, 'location', 1876, 743),
('Review Đồ Ship', 'review-do-ship', 'Review túi giữ nhiệt, balo, áo mưa shipper', '1. Có ảnh thật\n2. Ghi rõ giá', 1, 'review', 654, 189),
('Confession Shipper', 'confession-shipper', 'Tâm sự ẩn danh của shipper', '1. Ẩn danh\n2. Tôn trọng', 1, 'confession', 3200, 1456),
('Mẹo Tiết Kiệm Xăng', 'meo-tiet-kiem-xang', 'Tiết kiệm xăng, bảo dưỡng xe, route tối ưu', '1. Mẹo thực tế\n2. Có số liệu', 1, 'tips', 987, 345),
('Shipper J&T - SPX - Ninja Van', 'shipper-jt-spx-ninjavan', 'Hội shipper J&T, Shopee Express, Ninja Van', '1. Chia sẻ kinh nghiệm\n2. Hỗ trợ nhau', 1, 'shipping', 1567, 678);

INSERT INTO `group_members` (`group_id`, `user_id`, `role`) SELECT id, 1, 'admin' FROM `groups` WHERE creator_id = 1;
