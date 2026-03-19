<?php
/**
 * CONTENT AUTOMATION ENGINE
 * Chạy tự động qua cron hoặc webhook trigger
 * 
 * GET  ?action=generate     - Tạo content từ bài viết hay nhất
 * GET  ?action=queue        - Xem hàng đợi content
 * GET  ?action=publish      - Publish content đến lịch
 * GET  ?action=stats        - Thống kê marketing
 * POST ?action=schedule     - Thêm content vào queue
 * GET  ?action=cron         - Endpoint cho external cron (cron-job.org)
 * 
 * CRON SETUP: 
 * Đăng ký tại cron-job.org (free) → gọi URL mỗi giờ:
 * https://shippershop.vn/api/marketing-engine.php?action=cron&key=MARKETING_CRON_KEY
 */
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
$d = db();
$action = $_GET['action'] ?? '';

define('CRON_KEY', 'ss_mkt_' . substr(md5(JWT_SECRET . 'marketing'), 0, 16));

function mOk($data = null, $msg = 'OK') { echo json_encode(['success' => true, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE); exit; }
function mErr($msg) { echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE); exit; }

// ===== AUTO-GENERATE CONTENT FROM TOP POSTS =====
if ($action === 'generate') {
    // Admin only
    require_once __DIR__ . '/auth-check.php';
    $uid = getAuthUserId();
    $admin = $d->fetchOne("SELECT role FROM users WHERE id = ?", [$uid]);
    if (!$admin || $admin['role'] !== 'admin') mErr('Admin only');
    
    $type = $_GET['type'] ?? 'all'; // tiktok, facebook, blog, all
    $generated = generateContent($d, $type);
    mOk($generated);
}

// ===== VIEW QUEUE =====
if ($action === 'queue') {
    require_once __DIR__ . '/auth-check.php';
    $uid = getAuthUserId();
    $status = $_GET['status'] ?? 'pending';
    $items = $d->fetchAll("SELECT * FROM content_queue WHERE `status` = ? ORDER BY scheduled_at ASC LIMIT 50", [$status]);
    mOk($items);
}

// ===== PUBLISH SCHEDULED CONTENT =====
if ($action === 'publish') {
    require_once __DIR__ . '/auth-check.php';
    $uid = getAuthUserId();
    $results = publishScheduled($d);
    mOk($results);
}

// ===== CRON ENDPOINT (external cron service) =====
if ($action === 'cron') {
    $key = $_GET['key'] ?? '';
    if ($key !== CRON_KEY) mErr('Invalid key');
    
    $hour = (int)date('G'); // 0-23, Vietnam time (UTC+7)
    $results = ['time' => date('Y-m-d H:i:s'), 'hour' => $hour, 'actions' => []];
    
    // Every hour: check and publish scheduled content
    $published = publishScheduled($d);
    $results['actions'][] = ['publish' => $published];
    
    // 6 AM: Generate daily content
    if ($hour === 6 || $hour === 5) { // UTC+7 offset
        $generated = generateContent($d, 'all');
        $results['actions'][] = ['generate' => $generated];
    }
    
    // 9 AM: Update streaks for users
    if ($hour === 9 || $hour === 2) {
        updateDailyStreaks($d);
        $results['actions'][] = ['streaks' => 'updated'];
    }
    
    // 12 PM: Generate midday content
    if ($hour === 12 || $hour === 5) {
        $generated = generateContent($d, 'facebook');
        $results['actions'][] = ['midday_content' => $generated];
    }
    
    mOk($results, 'Cron executed');
}

// ===== MARKETING STATS =====
if ($action === 'stats') {
    require_once __DIR__ . '/auth-check.php';
    $stats = [
        'content_queue' => [
            'pending' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status` = 'pending'")['c'],
            'published' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status` = 'published'")['c'],
            'failed' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status` = 'failed'")['c'],
        ],
        'referrals' => [
            'total' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM referral_logs")['c'],
            'this_month' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM referral_logs WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")['c'],
        ],
        'gamification' => [
            'users_with_xp' => (int)$d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM user_xp")['c'],
            'total_xp_awarded' => (int)$d->fetchOne("SELECT COALESCE(SUM(xp),0) as c FROM user_xp")['c'],
            'active_streaks' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM user_streaks WHERE current_streak > 0")['c'],
        ],
        'cron_key' => CRON_KEY,
        'cron_url' => 'https://shippershop.vn/api/marketing-engine.php?action=cron&key=' . CRON_KEY,
    ];
    mOk($stats);
}

// ===== MANUAL SCHEDULE =====
if ($action === 'schedule' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/auth-check.php';
    $uid = getAuthUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'facebook';
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
    $scheduledAt = $input['scheduled_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    if (!$content) mErr('Content required');
    
    $d->query("INSERT INTO content_queue (type, title, content, scheduled_at) VALUES (?, ?, ?, ?)",
        [$type, $title, $content, $scheduledAt]);
    mOk(['id' => $d->getLastInsertId(), 'scheduled_at' => $scheduledAt]);
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function generateContent($d, $type) {
    $results = [];
    
    // Get top posts from last 24 hours (most likes + comments)
    $topPosts = $d->fetchAll("SELECT p.*, u.fullname as user_name, u.shipping_company 
        FROM posts p JOIN users u ON p.user_id = u.id 
        WHERE p.`status` = 'active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY (p.likes_count * 2 + p.comments_count * 3 + p.shares_count) DESC LIMIT 5");
    
    if (empty($topPosts)) {
        // Fallback: get random good posts
        $topPosts = $d->fetchAll("SELECT p.*, u.fullname as user_name, u.shipping_company 
            FROM posts p JOIN users u ON p.user_id = u.id 
            WHERE p.`status` = 'active' AND p.likes_count >= 3 
            ORDER BY RAND() LIMIT 3");
    }
    
    foreach ($topPosts as $post) {
        $content = $post['content'];
        if (mb_strlen($content) < 20) continue;
        
        // Generate different formats for different platforms
        if ($type === 'all' || $type === 'facebook') {
            $fbContent = generateFacebookPost($post);
            if ($fbContent) {
                // Schedule for next available slot (8h, 12h, 18h)
                $scheduleTime = getNextSlot('facebook');
                $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES ('facebook', ?, ?, ?, ?)",
                    [mb_substr($content, 0, 50) . '...', $fbContent, $post['id'], $scheduleTime]);
                $results[] = ['type' => 'facebook', 'post_id' => $post['id'], 'scheduled' => $scheduleTime];
            }
        }
        
        if ($type === 'all' || $type === 'tiktok') {
            $tkContent = generateTikTokScript($post);
            if ($tkContent) {
                $scheduleTime = getNextSlot('tiktok');
                $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES ('tiktok', ?, ?, ?, ?)",
                    [mb_substr($content, 0, 50) . '...', $tkContent, $post['id'], $scheduleTime]);
                $results[] = ['type' => 'tiktok', 'post_id' => $post['id'], 'scheduled' => $scheduleTime];
            }
        }
        
        if ($type === 'all' || $type === 'blog') {
            // Only 1 blog post per day
            $existingBlog = $d->fetchOne("SELECT id FROM content_queue WHERE type='blog' AND DATE(scheduled_at)=CURDATE()");
            if (!$existingBlog) {
                $blogContent = generateBlogPost($post);
                $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES ('blog', ?, ?, ?, ?)",
                    ['Blog: ' . mb_substr($content, 0, 40), $blogContent, $post['id'], date('Y-m-d 10:00:00')]);
                $results[] = ['type' => 'blog', 'post_id' => $post['id']];
            }
        }
    }
    
    // Generate daily tip
    if ($type === 'all' || $type === 'push') {
        $tip = generateDailyTip($d);
        if ($tip) {
            $d->query("INSERT INTO content_queue (type, title, content, scheduled_at) VALUES ('push', 'Daily Tip', ?, ?)",
                [$tip, date('Y-m-d 07:00:00')]);
            $results[] = ['type' => 'push', 'content' => $tip];
        }
    }
    
    return $results;
}

function generateFacebookPost($post) {
    $name = $post['user_name'] ?? 'Shipper';
    $company = $post['shipping_company'] ?? '';
    $content = $post['content'];
    
    // Format for Facebook
    $fb = "💬 Chia sẻ từ cộng đồng ShipperShop:\n\n";
    $fb .= "\"" . mb_substr($content, 0, 300) . "\"\n\n";
    if ($company) $fb .= "🏍️ Shipper " . $company . "\n";
    $fb .= "👍 " . $post['likes_count'] . " đơn giao thành công · 💬 " . $post['comments_count'] . " ghi chú\n\n";
    $fb .= "📱 Tham gia cộng đồng shipper lớn nhất VN:\n";
    $fb .= "👉 shippershop.vn\n\n";
    $fb .= "#shipper #giaohang #shippershop #congdongshipper";
    if ($company) $fb .= " #" . strtolower(str_replace([' ', '&'], '', $company));
    
    return $fb;
}

function generateTikTokScript($post) {
    $content = $post['content'];
    if (mb_strlen($content) < 30) return null;
    
    // TikTok caption format
    $tk = "🔥 " . mb_substr($content, 0, 150) . "\n\n";
    $tk .= "Ae shipper nghĩ sao? Comment bên dưới! 👇\n\n";
    $tk .= "#shipper #giaohang #shippervietnam #shippershop #congdongshipper #tipsshipper";
    
    return $tk;
}

function generateBlogPost($post) {
    $content = $post['content'];
    $name = $post['user_name'] ?? 'Shipper';
    
    $blog = "<h1>" . mb_substr($content, 0, 60) . "</h1>\n";
    $blog .= "<p>Chia sẻ từ " . htmlspecialchars($name) . " trên ShipperShop:</p>\n";
    $blog .= "<blockquote>" . htmlspecialchars($content) . "</blockquote>\n";
    $blog .= "<p>Bạn có kinh nghiệm tương tự? Tham gia thảo luận tại <a href='https://shippershop.vn'>ShipperShop</a>!</p>\n";
    
    return $blog;
}

function generateDailyTip($d) {
    $tips = [
        "💡 Mẹo tiết kiệm xăng: Giữ tốc độ 40-50km/h ổn định, tránh tăng giảm ga đột ngột",
        "💡 Kiểm tra lốp xe mỗi sáng trước khi bắt đầu ca, lốp non hao xăng + nguy hiểm",
        "💡 Luôn giữ bình nước theo xe, nhất là mùa nắng. Dehydration giảm phản xạ",
        "💡 Chụp ảnh hàng trước khi giao, đề phòng khiếu nại",
        "💡 Giờ cao điểm: 11-13h và 17-19h. Tránh kẹt bằng cách đi đường nhỏ",
        "💡 Sạc dự phòng là đồ nghề bắt buộc. Hết pin = mất đơn",
        "💡 Tải ShipperShop để cập nhật cảnh báo giao thông real-time từ ae shipper khác",
        "💡 Mưa lớn: bọc hàng 2 lớp nilon. Khách đánh giá 5 sao vì hàng khô ráo",
    ];
    return $tips[array_rand($tips)];
}

function getNextSlot($platform) {
    $d = db();
    $slots = ['08:00:00', '12:00:00', '18:00:00'];
    $today = date('Y-m-d');
    
    foreach ($slots as $slot) {
        $dt = $today . ' ' . $slot;
        if (strtotime($dt) < time()) continue; // Skip past slots
        $exists = $d->fetchOne("SELECT id FROM content_queue WHERE type = ? AND scheduled_at = ?", [$platform, $dt]);
        if (!$exists) return $dt;
    }
    
    // All slots today taken, schedule for tomorrow 8 AM
    return date('Y-m-d', strtotime('+1 day')) . ' 08:00:00';
}

function publishScheduled($d) {
    $results = [];
    $pending = $d->fetchAll("SELECT * FROM content_queue WHERE `status` = 'pending' AND scheduled_at <= NOW() ORDER BY scheduled_at LIMIT 10");
    
    foreach ($pending as $item) {
        $success = false;
        $error = '';
        
        switch ($item['type']) {
            case 'facebook':
                // Check if Facebook account configured
                $fb = $d->fetchOne("SELECT * FROM social_accounts WHERE platform = 'facebook' AND is_active = 1");
                if ($fb && $fb['access_token'] && $fb['page_id']) {
                    $success = publishToFacebook($fb, $item);
                } else {
                    $error = 'Facebook account not configured';
                    // Still log the content for manual posting
                    $success = false;
                }
                break;
                
            case 'tiktok':
                $error = 'TikTok API requires manual video upload';
                break;
                
            case 'blog':
                $success = publishBlogPost($d, $item);
                break;
                
            case 'push':
                $success = sendPushNotification($d, $item);
                break;
        }
        
        $d->query("UPDATE content_queue SET `status` = ?, published_at = ?, error_log = ? WHERE id = ?",
            [$success ? 'published' : 'failed', $success ? date('Y-m-d H:i:s') : null, $error, $item['id']]);
        
        $results[] = ['id' => $item['id'], 'type' => $item['type'], 'success' => $success, 'error' => $error];
    }
    
    return $results;
}

function publishToFacebook($account, $item) {
    $url = "https://graph.facebook.com/v19.0/" . $account['page_id'] . "/feed";
    $data = ['message' => $item['content'], 'access_token' => $account['access_token']];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $resp = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($resp, true);
    if (isset($result['id'])) {
        db()->query("UPDATE content_queue SET platform_post_id = ? WHERE id = ?", [$result['id'], $item['id']]);
        return true;
    }
    return false;
}

function publishBlogPost($d, $item) {
    // Save as a special post type in posts table
    try {
        $d->query("INSERT INTO posts (user_id, content, type, `status`, created_at) VALUES (2, ?, 'tip', 'active', NOW())",
            [strip_tags($item['content'])]);
        return true;
    } catch (Throwable $e) { return false; }
}

function sendPushNotification($d, $item) {
    // Use existing push system
    try {
        require_once __DIR__ . '/../includes/push-helper.php';
        $subs = $d->fetchAll("SELECT * FROM push_subscriptions LIMIT 500");
        foreach ($subs as $sub) {
            try { sendPush($sub, 'ShipperShop', $item['content'], '/index.html'); } catch (Throwable $e) {}
        }
        return true;
    } catch (Throwable $e) { return false; }
}

function updateDailyStreaks($d) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Users who posted or commented today
    $activeUsers = $d->fetchAll("SELECT DISTINCT user_id FROM (
        SELECT user_id FROM posts WHERE DATE(created_at) = ? AND `status` = 'active'
        UNION SELECT user_id FROM comments WHERE DATE(created_at) = ? AND `status` = 'active'
    ) as active", [$today, $today]);
    
    foreach ($activeUsers as $u) {
        $uid = $u['user_id'];
        $streak = $d->fetchOne("SELECT * FROM user_streaks WHERE user_id = ?", [$uid]);
        
        if (!$streak) {
            $d->query("INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_active_date, total_xp) VALUES (?, 1, 1, ?, 0)",
                [$uid, $today]);
        } else {
            $lastDate = $streak['last_active_date'];
            if ($lastDate === $today) continue; // Already counted
            
            $newStreak = ($lastDate === $yesterday) ? $streak['current_streak'] + 1 : 1;
            $longest = max($newStreak, $streak['longest_streak']);
            
            $d->query("UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_active_date = ? WHERE user_id = ?",
                [$newStreak, $longest, $today, $uid]);
            
            // Award streak XP
            if ($newStreak === 7) {
                $d->query("INSERT INTO user_xp (user_id, action, xp, detail) VALUES (?, 'streak_7', 50, '7 ngày liên tục!')", [$uid]);
            } elseif ($newStreak === 30) {
                $d->query("INSERT INTO user_xp (user_id, action, xp, detail) VALUES (?, 'streak_30', 200, '30 ngày liên tục!')", [$uid]);
            }
        }
    }
}

mErr('Invalid action');
