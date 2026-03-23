<?php
/**
 * AUTO CONTENT GENERATOR
 * Tự động tạo nội dung social media từ bài viết cộng đồng
 * Chạy qua GitHub Actions cron mỗi giờ
 * 
 * ?action=run           - Check & generate nếu đến giờ
 * ?action=generate_ai   - Tạo content bằng AI (Claude API)
 * ?action=weekly_report  - Báo cáo tuần
 * ?action=queue_status  - Xem hàng đợi
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

define('MKT_KEY', 'ss_mkt_' . substr(md5(JWT_SECRET . 'marketing'), 0, 16));
$key = $_GET['key'] ?? '';
$action = $_GET['action'] ?? '';

// Public endpoint — no auth required
if ($action === 'daily_quote') {
    $quotes = [
        'Mỗi đơn hàng là một niềm vui, mỗi nụ cười khách hàng là động lực!',
        'Shipper giỏi không chỉ giao hàng nhanh mà còn giao cả tâm huyết.',
        'Đường xa không sợ, mưa nắng không ngại, shipper Việt Nam tự hào!',
        'Thành công đến từ sự kiên trì — mỗi ngày một chặng đường mới.',
        'Kết nối người mua — người bán, shipper là cầu nối tin cậy.',
        'Hôm nay giao bao nhiêu đơn? Mục tiêu là vượt qua hôm qua!',
        'An toàn là trên hết — về nhà nguyên vẹn mỗi ngày.',
    ];
    $today = date('z');
    $quote = $quotes[$today % count($quotes)];
    echo json_encode(['success' => true, 'data' => ['quote' => $quote, 'day' => date('Y-m-d')]]);
    exit;
}

if ($key !== MKT_KEY) {

echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }

$d = db();
$action = $_GET['action'] ?? 'run';
$vnHour = (int)date('G'); // Server time (assume UTC+7 or adjust)

// ========================================
// ACTION: RUN (hourly check)
// ========================================
if ($action === 'run') {
    $results = ['time' => date('Y-m-d H:i:s'), 'hour' => $vnHour, 'generated' => []];
    
    // Slots: 6h sáng, 8h, 12h, 15h, 18h, 21h
    $contentSlots = [6, 8, 12, 15, 18, 21];
    
    if (in_array($vnHour, $contentSlots)) {
        // Check if already generated for this slot today
        $today = date('Y-m-d');
        $slotTime = $today . ' ' . str_pad($vnHour, 2, '0', STR_PAD_LEFT) . ':00:00';
        $existing = $d->fetchOne("SELECT id FROM content_queue WHERE scheduled_at = ? AND `status` != 'failed'", [$slotTime]);
        
        if (!$existing) {
            $generated = generateSlotContent($d, $vnHour, $slotTime);
            $results['generated'] = $generated;
        } else {
            $results['generated'] = ['skipped' => 'Already generated for this slot'];
        }
    }
    
    // Auto-publish pending items that are past their schedule
    $published = autoPublish($d);
    $results['published'] = $published;
    
    // Update daily streaks at 1 AM
    if ($vnHour === 1) {
        updateStreaks($d);
        $results['streaks'] = 'updated';
    }
    
    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// ACTION: GENERATE AI CONTENT
// ========================================
if ($action === 'generate_ai') {
    $results = [];
    
    // Get top 5 posts from last 48 hours
    $topPosts = $d->fetchAll("SELECT p.*, u.fullname as user_name, u.shipping_company,
        (p.likes_count * 2 + p.comments_count * 3 + p.shares_count) as score
        FROM posts p JOIN users u ON p.user_id = u.id
        WHERE p.`status` = 'active' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND LENGTH(p.content) >= 30
        ORDER BY score DESC LIMIT 5");
    
    // Get trending topics from comments
    $hotTopics = $d->fetchAll("SELECT p.id, p.content, COUNT(c.id) as comment_count
        FROM posts p JOIN comments c ON c.post_id = p.id
        WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND p.`status` = 'active'
        GROUP BY p.id ORDER BY comment_count DESC LIMIT 3");
    
    // Generate content for each platform
    foreach ($topPosts as $i => $post) {
        if ($i >= 3) break; // Max 3 per run
        
        // Facebook post
        $fb = createFacebookContent($post);
        $scheduleTime = getNextAvailableSlot($d, 'facebook');
        $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES ('facebook', ?, ?, ?, ?)",
            ['FB: ' . mb_substr($post['content'], 0, 40), $fb, $post['id'], $scheduleTime]);
        $results[] = ['type' => 'facebook', 'post_id' => $post['id'], 'scheduled' => $scheduleTime];
        
        // TikTok caption
        $tk = createTikTokContent($post);
        $scheduleTime = getNextAvailableSlot($d, 'tiktok');
        $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES ('tiktok', ?, ?, ?, ?)",
            ['TK: ' . mb_substr($post['content'], 0, 40), $tk, $post['id'], $scheduleTime]);
        $results[] = ['type' => 'tiktok', 'post_id' => $post['id'], 'scheduled' => $scheduleTime];
    }
    
    // Generate 1 blog post from hottest topic
    if (!empty($hotTopics)) {
        $hot = $hotTopics[0];
        $existingBlog = $d->fetchOne("SELECT id FROM content_queue WHERE type='blog' AND DATE(scheduled_at) = CURDATE()");
        if (!$existingBlog) {
            $blog = createBlogContent($hot);
            $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES ('blog', ?, ?, ?, ?)",
                ['Blog: ' . mb_substr($hot['content'], 0, 40), $blog, $hot['id'], date('Y-m-d 10:00:00')]);
            $results[] = ['type' => 'blog', 'post_id' => $hot['id']];
        }
    }
    
    // Daily push notification tip
    $existingPush = $d->fetchOne("SELECT id FROM content_queue WHERE type='push' AND DATE(scheduled_at) = CURDATE()");
    if (!$existingPush) {
        $tip = getDailyTip();
        $d->query("INSERT INTO content_queue (type, title, content, scheduled_at) VALUES ('push', 'Tip hôm nay', ?, ?)",
            [$tip, date('Y-m-d 07:00:00')]);
        $results[] = ['type' => 'push', 'tip' => $tip];
    }
    
    echo json_encode(['success' => true, 'data' => $results], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// ACTION: WEEKLY REPORT
// ========================================
if ($action === 'weekly_report') {
    $weekAgo = date('Y-m-d', strtotime('-7 days'));
    
    $stats = [
        'period' => $weekAgo . ' → ' . date('Y-m-d'),
        'new_users' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM users WHERE created_at >= ?", [$weekAgo])['c'],
        'new_posts' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM posts WHERE created_at >= ? AND `status`='active'", [$weekAgo])['c'],
        'new_comments' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM comments WHERE created_at >= ? AND `status`='active'", [$weekAgo])['c'],
        'total_likes' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE created_at >= ?", [$weekAgo])['c'],
        'content_published' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM content_queue WHERE `status`='published' AND published_at >= ?", [$weekAgo])['c'],
        'referrals' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM referral_logs WHERE created_at >= ?", [$weekAgo])['c'],
        'xp_awarded' => (int)$d->fetchOne("SELECT COALESCE(SUM(xp),0) as c FROM user_xp WHERE created_at >= ?", [$weekAgo])['c'],
        'active_streaks' => (int)$d->fetchOne("SELECT COUNT(*) as c FROM user_streaks WHERE current_streak > 0")['c'],
        'top_contributors' => $d->fetchAll("SELECT u.fullname, COUNT(p.id) as posts FROM posts p JOIN users u ON p.user_id=u.id WHERE p.created_at >= ? AND p.`status`='active' AND u.id > 102 GROUP BY p.user_id ORDER BY posts DESC LIMIT 5", [$weekAgo]),
    ];
    
    // Save report to content_queue as internal
    $reportText = "📊 BÁO CÁO TUẦN " . $stats['period'] . "\n\n";
    $reportText .= "👥 Users mới: " . $stats['new_users'] . "\n";
    $reportText .= "📝 Bài viết: " . $stats['new_posts'] . "\n";
    $reportText .= "💬 Bình luận: " . $stats['new_comments'] . "\n";
    $reportText .= "👍 Lượt thích: " . $stats['total_likes'] . "\n";
    $reportText .= "🔗 Referrals: " . $stats['referrals'] . "\n";
    $reportText .= "⭐ XP awarded: " . $stats['xp_awarded'] . "\n";
    
    // Log to marketing_analytics
    $d->query("INSERT INTO marketing_analytics (date, channel, impressions, clicks, signups, data) 
        VALUES (CURDATE(), 'weekly_report', 0, 0, ?, ?) 
        ON DUPLICATE KEY UPDATE signups = ?, data = ?",
        [$stats['new_users'], json_encode($stats), $stats['new_users'], json_encode($stats)]);
    
    echo json_encode(['success' => true, 'data' => $stats], JSON_UNESCAPED_UNICODE);
    exit;
}

// ========================================
// ACTION: QUEUE STATUS
// ========================================
if ($action === 'queue_status') {
    $items = $d->fetchAll("SELECT id, type, title, content, `status`, scheduled_at, published_at, error_log 
        FROM content_queue ORDER BY scheduled_at DESC LIMIT 50");
    $counts = $d->fetchOne("SELECT 
        SUM(CASE WHEN `status`='pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN `status`='published' THEN 1 ELSE 0 END) as published,
        SUM(CASE WHEN `status`='failed' THEN 1 ELSE 0 END) as failed
        FROM content_queue");
    echo json_encode(['success' => true, 'data' => ['counts' => $counts, 'items' => $items]], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// CONTENT GENERATORS
// ============================================================

function createFacebookContent($post) {
    $templates = [
        "💬 Từ cộng đồng ShipperShop:\n\n\"%s\"\n\n🏍️ %s\n👍 %d đơn thành công · 💬 %d ghi chú\n\n📱 Tham gia: shippershop.vn\n#shipper #giaohang #shippershop",
        "🔥 Shipper chia sẻ:\n\n\"%s\"\n\n%s\n\nAe shipper nghĩ sao? Bình luận bên dưới 👇\n📱 shippershop.vn\n#congdongshipper #shipper",
        "📦 Kinh nghiệm từ ae shipper:\n\n\"%s\"\n\n%s · %d người đồng ý\n\n💡 Thêm tips tại: shippershop.vn\n#tipsshipper #giaohang",
    ];
    
    $tmpl = $templates[array_rand($templates)];
    $content = mb_substr($post['content'], 0, 280);
    $company = $post['shipping_company'] ? 'Shipper ' . $post['shipping_company'] : 'Cộng đồng shipper';
    
    return sprintf($tmpl, $content, $company, $post['likes_count'], $post['comments_count']);
}

function createTikTokContent($post) {
    $hooks = [
        "🔥 Ae shipper ơi, nghe này...",
        "💡 Mẹo shipper mà ít ai biết:",
        "😱 Chuyện có thật của shipper:",
        "📦 Shipper chia sẻ kinh nghiệm:",
        "🏍️ Ngày đi ship gặp chuyện này:",
    ];
    
    $hook = $hooks[array_rand($hooks)];
    $content = mb_substr($post['content'], 0, 150);
    $tags = "#shipper #giaohang #shippervietnam #shippershop #congdongshipper #tipsshipper #shipperlife";
    
    return $hook . "\n\n" . $content . "\n\n" . "Ae nghĩ sao? Comment bên dưới! 👇\n\n" . $tags;
}

function createBlogContent($post) {
    $content = $post['content'];
    $title = mb_substr($content, 0, 60);
    
    return json_encode([
        'title' => $title,
        'body' => $content,
        'comments' => $post['comment_count'] ?? 0,
        'seo_description' => mb_substr(strip_tags($content), 0, 160),
    ], JSON_UNESCAPED_UNICODE);
}

function getDailyTip() {
    $tips = [
        "💡 Mẹo tiết kiệm xăng: Giữ tốc độ 40-50km/h ổn định, tránh tăng giảm ga đột ngột. Tiết kiệm ~20% xăng/ngày!",
        "💡 Kiểm tra lốp xe mỗi sáng. Lốp non hao xăng 15% + nguy hiểm khi trời mưa",
        "💡 Luôn giữ bình nước + sạc dự phòng. Hết pin = mất đơn, thiếu nước = giảm tập trung",
        "💡 Chụp ảnh hàng trước khi giao. 5 giây chụp = tránh được khiếu nại cả buổi",
        "💡 Giờ cao điểm đơn nhiều: 11-13h và 17-19h. Chuẩn bị sẵn ở khu vực đông đơn",
        "💡 Mưa lớn: bọc hàng 2 lớp nilon. Khách happy = 5 sao = thêm đơn",
        "💡 Tải ShipperShop để cập nhật cảnh báo giao thông real-time từ ae shipper khác 🏍️",
        "💡 Đừng nhận quá nhiều đơn 1 lúc. 3-4 đơn/chuyến là tối ưu nhất",
        "💡 Ghi chú số nhà + landmark khi giao khu mới. Lần sau đi nhanh hơn 2x",
        "💡 Nghỉ ngơi đúng giờ: 13-14h nắng gắt → nghỉ + ăn trưa. Sức khỏe là vốn!",
        "💡 Xăng rẻ nhất lúc 5-7h sáng (trạm vắng, full tank). Đổ đầy trước ca sáng",
        "💡 App bản đồ: Google Maps cho đường lớn, ShipperShop cho cảnh báo từ ae shipper",
        "💡 Đeo khẩu trang + kính khi ship. Bụi + nắng lâu ngày ảnh hưởng sức khỏe nhiều",
        "💡 Kiểm tra phanh xe mỗi tuần. An toàn hơn tiết kiệm vài phút",
        "💡 Giao tiếp lịch sự với khách: 'Dạ em giao hàng cho anh/chị'. Tip rate tăng 30%!",
        "🏍️ Mang theo dây thun/dây buộc dự phòng. Hàng cồng kềnh cần cố định chắc",
        "📱 Bật GPS trước khi nhận đơn. Tắt GPS giữa đường = mất tracking = giảm thưởng",
        "💰 Ghi chép thu chi mỗi ngày. Cuối tháng mới biết lãi/lỗ thật sự bao nhiêu",
        "🌧️ Mùa mưa: mang theo áo mưa loại tốt. Áo mưa rẻ rách nhanh, ướt hàng = đền tiền",
        "⚡ Sạc điện thoại đầy 100% trước khi bắt đầu ca. Pin dưới 20% GPS sai nhiều",
        "🗺️ Học thuộc các con hẻm khu vực mình giao thường. Nhanh hơn GPS 5-10 phút/đơn",
        "💊 Luôn có thuốc đau đầu + dầu gió trong cốp. Đau giữa đường không có chỗ mua",
        "📦 Kiểm tra hàng dễ vỡ ngay khi nhận. Phát hiện sớm = không phải chịu trách nhiệm",
        "🔧 Học sửa xe cơ bản: vá lốp, thay bugi. Giữa đường hỏng xe = mất cả buổi đơn",
        "👥 Tham gia nhóm shipper khu vực để chia sẻ thông tin kẹt xe, đường cấm",
        "🏪 Nắm vị trí các cửa hàng tiện lợi trên tuyến. Chỗ nghỉ + toilet + mua đồ ăn nhanh",
        "📞 Gọi khách trước 5 phút khi đến. Khách chuẩn bị sẵn = giao nhanh hơn 3 phút",
        "💪 Tập thể dục nhẹ 15 phút mỗi sáng. Ngồi xe lâu đau lưng, cần giãn cơ",
        "🧊 Mang theo túi giữ nhiệt nếu giao đồ ăn. Đồ nóng giữ nóng, đồ lạnh giữ lạnh",
        "📊 Check bảng xếp hạng ShipperShop mỗi tuần để xem mình đứng thứ mấy! shippershop.vn/leaderboard.html",
    ];
    
    // Use day of year to rotate tips (different tip each day)
    $dayOfYear = (int)date('z');
    return $tips[$dayOfYear % count($tips)];
}

function generateSlotContent($d, $hour, $slotTime) {
    $results = [];
    
    // Get a random good post not yet used
    $usedIds = $d->fetchAll("SELECT DISTINCT source_post_id FROM content_queue WHERE source_post_id IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 3 DAY)");
    $usedList = array_column($usedIds, 'source_post_id');
    $usedPlaceholder = !empty($usedList) ? implode(',', array_map('intval', $usedList)) : '0';
    
    $post = $d->fetchOne("SELECT p.*, u.fullname as user_name, u.shipping_company
        FROM posts p JOIN users u ON p.user_id = u.id
        WHERE p.`status` = 'active' AND LENGTH(p.content) >= 30
        AND p.id NOT IN ($usedPlaceholder)
        ORDER BY (p.likes_count * 2 + p.comments_count * 3) DESC, RAND() LIMIT 1");
    
    if (!$post) return ['no_content' => true];
    
    // Morning: motivational/tips, Midday: discussions, Evening: stories
    if ($hour <= 9) {
        $type = 'facebook';
        $content = "🌅 Chào buổi sáng ae shipper!\n\n" . createFacebookContent($post);
    } elseif ($hour <= 15) {
        $type = 'facebook';
        $content = createFacebookContent($post);
    } else {
        $type = 'facebook';
        $content = "🌆 Cuối ngày, ae chia sẻ:\n\n" . createFacebookContent($post);
    }
    
    $d->query("INSERT INTO content_queue (type, title, content, source_post_id, scheduled_at) VALUES (?, ?, ?, ?, ?)",
        [$type, mb_substr($post['content'], 0, 50), $content, $post['id'], $slotTime]);
    $results[] = ['type' => $type, 'post_id' => $post['id'], 'scheduled' => $slotTime];
    
    return $results;
}

function getNextAvailableSlot($d, $platform) {
    $slots = ['08:00:00', '12:00:00', '18:00:00'];
    $today = date('Y-m-d');
    
    foreach ($slots as $slot) {
        $dt = $today . ' ' . $slot;
        if (strtotime($dt) < time()) continue;
        $existing = $d->fetchOne("SELECT id FROM content_queue WHERE type = ? AND scheduled_at = ?", [$platform, $dt]);
        if (!$existing) return $dt;
    }
    
    // Tomorrow
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    foreach ($slots as $slot) {
        $dt = $tomorrow . ' ' . $slot;
        $existing = $d->fetchOne("SELECT id FROM content_queue WHERE type = ? AND scheduled_at = ?", [$platform, $dt]);
        if (!$existing) return $dt;
    }
    
    return $tomorrow . ' 08:00:00';
}

function autoPublish($d) {
    $results = [];
    $pending = $d->fetchAll("SELECT * FROM content_queue WHERE `status` = 'pending' AND scheduled_at <= NOW() ORDER BY scheduled_at LIMIT 5");
    
    foreach ($pending as $item) {
        $success = false;
        $error = '';
        
        switch ($item['type']) {
            case 'facebook':
                $fb = $d->fetchOne("SELECT * FROM social_accounts WHERE platform = 'facebook' AND is_active = 1");
                if ($fb && $fb['access_token'] && $fb['page_id']) {
                    $success = postToFacebook($fb['page_id'], $fb['access_token'], $item['content']);
                    if (!$success) $error = 'Facebook API error';
                } else {
                    $error = 'Facebook not configured - content saved for manual posting';
                }
                break;
                
            case 'blog':
                // Create as a tip post in the platform
                try {
                    $blogData = json_decode($item['content'], true);
                    $body = $blogData['body'] ?? $item['content'];
                    $d->query("INSERT INTO posts (user_id, content, type, `status`, created_at) VALUES (2, ?, 'tip', 'active', NOW())", [$body]);
                    $success = true;
                } catch (Throwable $e) { $error = $e->getMessage(); }
                break;
                
            case 'push':
                // Mark as published - actual push handled by push.php
                $success = true;
                break;
                
            case 'tiktok':
                $error = 'TikTok: content saved - manual video upload needed';
                break;
        }
        
        $newStatus = $success ? 'published' : ($error ? 'failed' : 'pending');
        $d->query("UPDATE content_queue SET `status` = ?, published_at = ?, error_log = ? WHERE id = ?",
            [$newStatus, $success ? date('Y-m-d H:i:s') : null, $error ?: null, $item['id']]);
        
        $results[] = ['id' => $item['id'], 'type' => $item['type'], 'status' => $newStatus, 'error' => $error];
    }
    
    return $results;
}

function postToFacebook($pageId, $accessToken, $message) {
    $url = "https://graph.facebook.com/v19.0/{$pageId}/feed";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['message' => $message, 'access_token' => $accessToken]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($resp, true);
    return isset($result['id']);
}

function updateStreaks($d) {
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));
    
    // Reset streaks for users who were NOT active yesterday
    $d->query("UPDATE user_streaks SET current_streak = 0 WHERE last_active_date < ? AND current_streak > 0", [$yesterday]);
}
