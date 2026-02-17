-- SHIPPERSHOP - COMPLETE DATABASE SCHEMA
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `status` enum('active','inactive','banned') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0,
  `original_price` decimal(15,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `sold` int(11) DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `images` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 5.00,
  `review_count` int(11) DEFAULT 0,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `is_flash_sale` tinyint(1) DEFAULT 0,
  `flash_sale_end` datetime DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `status` enum('pending','confirmed','processing','shipping','completed','cancelled','refunded') DEFAULT 'pending',
  `subtotal` decimal(15,2) DEFAULT 0,
  `shipping_fee` decimal(15,2) DEFAULT 30000,
  `discount` decimal(15,2) DEFAULT 0,
  `total` decimal(15,2) DEFAULT 0,
  `payment_method` enum('cod','bank_transfer','wallet','momo','vnpay') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `shipping_name` varchar(100) DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `note` text DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(15,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `comment` text DEFAULT NULL,
  `images` text DEFAULT NULL,
  `helpful` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `type` enum('percent','fixed') DEFAULT 'percent',
  `value` decimal(10,2) NOT NULL,
  `min_order` decimal(15,2) DEFAULT 0,
  `max_discount` decimal(15,2) DEFAULT NULL,
  `uses_limit` int(11) DEFAULT NULL,
  `uses_count` int(11) DEFAULT 0,
  `end_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `images` text DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `type` enum('post','review','question','tip') DEFAULT 'post',
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `hashtags` varchar(500) DEFAULT NULL,
  `status` enum('active','deleted','hidden') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `likes_count` int(11) DEFAULT 0,
  `status` enum('active','deleted') DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_post` (`user_id`, `post_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `follows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `follow_pair` (`follower_id`, `following_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `data` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0,
  `total_deposit` decimal(15,2) DEFAULT 0,
  `total_withdraw` decimal(15,2) DEFAULT 0,
  `total_spent` decimal(15,2) DEFAULT 0,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `wallet_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','withdraw','payment','refund','bonus') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance_before` decimal(15,2) DEFAULT 0,
  `balance_after` decimal(15,2) DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `reference_id` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `proof_image` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','rejected','cancelled') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `province` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `address` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
('site_name', 'ShipperShop'),
('site_email', 'support@shippershop.vn'),
('shipping_fee', '30000'),
('free_shipping_from', '500000'),
('bank_name', 'Vietcombank'),
('bank_account', '1234567890'),
('bank_owner', 'SHIPPERSHOP JSC'),
('maintenance_mode', '0'),
('allow_registration', '1');

INSERT IGNORE INTO `coupons` (`code`, `type`, `value`, `min_order`, `status`) VALUES
('WELCOME10', 'percent', 10, 0, 'active'),
('SALE50K', 'fixed', 50000, 200000, 'active'),
('FREESHIP', 'fixed', 30000, 0, 'active');
