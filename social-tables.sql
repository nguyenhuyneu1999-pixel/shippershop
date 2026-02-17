-- ============================================
-- SOCIAL FEATURES - ADDITIONAL TABLES
-- ============================================
-- Run this SQL AFTER importing the main database.sql
-- These tables support hashtags and saved posts features

-- Hashtags table
CREATE TABLE IF NOT EXISTS `hashtags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(100) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tag` (`tag`),
  KEY `idx_post_id` (`post_id`),
  CONSTRAINT `fk_hashtags_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Saved posts table (bookmarks)
CREATE TABLE IF NOT EXISTS `saved_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_saved_post` (`user_id`, `post_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_post_id` (`post_id`),
  CONSTRAINT `fk_saved_posts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saved_posts_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample hashtags
INSERT INTO `hashtags` (`tag`, `post_id`, `created_at`) VALUES
('review', 1, NOW()),
('iphone15', 1, NOW()),
('technology', 1, NOW()),
('shopping', 2, NOW()),
('sale', 2, NOW()),
('tips', 3, NOW());

-- ============================================
-- INDEXES FOR PERFORMANCE
-- ============================================

-- Add indexes to posts table for better performance
ALTER TABLE `posts` 
ADD INDEX `idx_user_created` (`user_id`, `created_at`),
ADD INDEX `idx_status_created` (`status`, `created_at`);

-- Add indexes to comments table
ALTER TABLE `comments`
ADD INDEX `idx_post_status` (`post_id`, `status`);

-- Add indexes to post_likes table
ALTER TABLE `post_likes`
ADD INDEX `idx_post_user` (`post_id`, `user_id`);

-- Add indexes to follows table
ALTER TABLE `follows`
ADD INDEX `idx_follower` (`follower_id`),
ADD INDEX `idx_following` (`following_id`);

-- ============================================
-- VIEWS FOR COMMON QUERIES
-- ============================================

-- View: Posts with full details
CREATE OR REPLACE VIEW `v_posts_full` AS
SELECT 
    p.id,
    p.user_id,
    p.content,
    p.image_url,
    p.status,
    p.created_at,
    p.updated_at,
    u.fullname as user_name,
    u.avatar as user_avatar,
    u.email as user_email,
    (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count,
    (SELECT COUNT(*) FROM saved_posts WHERE post_id = p.id) as saves_count
FROM posts p
LEFT JOIN users u ON p.user_id = u.id
WHERE p.status = 'active';

-- View: User stats
CREATE OR REPLACE VIEW `v_user_stats` AS
SELECT 
    u.id as user_id,
    u.fullname,
    u.email,
    u.avatar,
    (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND status = 'active') as posts_count,
    (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count,
    (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
    (SELECT COUNT(*) FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = u.id) as total_likes_received
FROM users u
WHERE u.status = 'active';

-- View: Trending hashtags (last 7 days)
CREATE OR REPLACE VIEW `v_trending_hashtags` AS
SELECT 
    h.tag,
    COUNT(*) as posts_count,
    MAX(h.created_at) as last_used
FROM hashtags h
WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY h.tag
ORDER BY posts_count DESC
LIMIT 10;

-- ============================================
-- STORED PROCEDURES (Optional)
-- ============================================

-- Procedure: Get user feed (posts from following users)
DELIMITER //

CREATE PROCEDURE `sp_get_user_feed`(
    IN p_user_id INT,
    IN p_limit INT,
    IN p_offset INT
)
BEGIN
    SELECT 
        p.*,
        u.fullname as user_name,
        u.avatar as user_avatar,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count,
        EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = p_user_id) as user_liked,
        EXISTS(SELECT 1 FROM saved_posts WHERE post_id = p.id AND user_id = p_user_id) as user_saved
    FROM posts p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.user_id IN (
        SELECT following_id FROM follows WHERE follower_id = p_user_id
    )
    AND p.status = 'active'
    ORDER BY p.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END //

DELIMITER ;

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Update post likes count (already exists in main database)
-- Trigger: Update post comments count (already exists in main database)

-- ============================================
-- SAMPLE DATA FOR TESTING
-- ============================================

-- Insert more sample posts
INSERT INTO `posts` (`user_id`, `content`, `image_url`, `status`, `created_at`) VALUES
(1, 'Vừa mua iPhone 15 Pro Max tại ShipperShop, ship siêu nhanh! Ai cần tư vấn cứ hỏi nhé 📱 #iPhone15 #Review', NULL, 'active', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'Hôm nay có flash sale laptop giảm 30%! Mọi người săn ngay kẻo hết 💻 #Sale #Shopping', NULL, 'active', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'Mẹo: Mua sắm vào cuối tháng thường có nhiều deal hơn 💡 #Tips #ShoppingTips', NULL, 'active', DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- Insert sample comments
INSERT INTO `comments` (`post_id`, `user_id`, `content`, `status`) VALUES
(1, 1, 'Sản phẩm rất tốt, mình rất hài lòng!', 'active'),
(1, 1, 'Cảm ơn shop đã giao hàng nhanh', 'active'),
(2, 1, 'Màu sắc đẹp quá!', 'active');

-- Insert sample likes
INSERT INTO `post_likes` (`post_id`, `user_id`) VALUES
(1, 1),
(2, 1);

-- Done!
SELECT 'Social features tables created successfully!' as status;
