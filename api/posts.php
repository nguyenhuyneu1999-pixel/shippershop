<?php
session_start();
/**
 * ============================================
 * POSTS API
 * ============================================
 * 
 * Endpoints:
 * GET /api/posts.php - Get all posts (with pagination)
 * GET /api/posts.php?id=1 - Get single post
 * POST /api/posts.php - Create new post
 * PUT /api/posts.php - Update post
 * DELETE /api/posts.php?id=1 - Delete post
 */

define('APP_ACCESS', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';
require_once __DIR__ . '/../includes/api-error-handler.php';
require_once __DIR__ . '/../includes/image-optimizer.php';
setupApiErrorHandler();
try { require_once __DIR__ . '/../includes/redis-rate-limiter.php'; apiRateLimit('posts.php'); } catch (Throwable $e) {}
require_once __DIR__ . '/../includes/async-notify.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../includes/xp-helper.php';

// Set CORS headers
setCorsHeaders();

// Get database
$db = db();

// Get request method
$method = getRequestMethod();

// ============================================
// GET POSTS
// ============================================

if ($method === 'GET') {

    // Action routing for GET
    $getAction = $_GET["action"] ?? null;
    
    if ($getAction === "user_profile") {
        $uid = intval($_GET["user_id"] ?? 0);
        if (!$uid) error("Missing user_id");
        // OPTIMIZED: 5 queries → 1 query with subqueries
        $followCheck = $userId ? ",(SELECT COUNT(*) FROM follows WHERE follower_id = $userId AND following_id = u.id) as is_following" : ",0 as is_following";
        $user = $db->fetchOne("SELECT u.id, u.fullname, u.username, u.avatar, u.email,
            (SELECT COUNT(*) FROM posts WHERE user_id = u.id AND `status`='active') as posts_count,
            (SELECT COUNT(*) FROM post_likes WHERE user_id = u.id) as likes_count,
            (SELECT COUNT(*) FROM comments WHERE user_id = u.id AND `status`='active') as comments_count
            $followCheck
            FROM users u WHERE u.id = ?", [$uid]);
        if (!$user) error("User not found");
        $user['is_following'] = (bool)intval($user['is_following'] ?? 0);
        success("OK", ["user" => $user]);
    }

    if ($getAction === "user_posts") {
        $uid = intval($_GET["user_id"] ?? 0);
        $tab = $_GET["tab"] ?? "posts";
        if ($tab === "posts") {
            $items = $db->fetchAll("SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company as shipping_company FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.user_id=? AND p.status='active' ORDER BY p.created_at DESC LIMIT 50", [$uid]);
        } elseif ($tab === "liked") {
            $items = $db->fetchAll("SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company as shipping_company FROM posts p LEFT JOIN users u ON p.user_id=u.id JOIN post_likes pl ON pl.post_id=p.id WHERE pl.user_id=? AND p.status='active' ORDER BY pl.created_at DESC LIMIT 50", [$uid]);
        } else {
            $items = $db->fetchAll("SELECT DISTINCT p.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company as shipping_company FROM posts p LEFT JOIN users u ON p.user_id=u.id JOIN comments c ON c.post_id=p.id WHERE c.user_id=? AND p.status='active' ORDER BY p.created_at DESC LIMIT 50", [$uid]);
        }
        success("OK", $items);
    }

    if ($getAction === "comments") {
        $pid = intval($_GET["post_id"] ?? 0);
        api_try_cache("comments_" . $pid, 15);
        $cmts = $db->fetchAll(
            "SELECT c.*, u.fullname as user_name, u.avatar as user_avatar 
             FROM comments c 
             LEFT JOIN users u ON c.user_id = u.id 
             WHERE c.post_id = ? AND c.status = 'active' 
             ORDER BY c.created_at ASC", [$pid]);
        success("OK", $cmts);
    }


    
    // Get single post
    if (isset($_GET['id'])) {
        $postId = intval($_GET['id']);
        
        $sql = "SELECT 
                    p.*,
                    u.fullname as user_name,
                    u.avatar as user_avatar,
                    u.username as user_username,
                    u.shipping_company as shipping_company
                FROM posts p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ? AND p.status = 'active'";
        
        $post = $db->fetchOne($sql, [$postId]);
        
        if (!$post) {
            error('Bài viết không tồn tại', 404);
        }
        
        $authUid = getOptionalAuthUserId();
        if ($authUid) {
            $liked = $db->fetchOne("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", [$postId, $authUid]);
            $post['user_liked'] = $liked ? true : false;
            $saved = $db->fetchOne("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?", [$postId, $authUid]);
            $post['user_saved'] = $saved ? true : false;
        } else {
            $post['user_liked'] = false;
            $post['user_saved'] = false;
        }
        
        success('Success', $post);
    }
    
    // Get all posts
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;
    
    // Build WHERE clause
    $where = ["p.status = 'active'"];
    $params = [];
    
    // Filter by user
    if (!empty($_GET['user_id'])) {
        $where[] = "p.user_id = ?";
        $params[] = intval($_GET['user_id']);
    }
    
    // Filter by hashtag
    if (!empty($_GET['hashtag'])) {
        $hashtag = sanitize($_GET['hashtag']);
        $where[] = "p.content LIKE ?";
        $params[] = "%#$hashtag%";
    }
    
    // Search
    if (!empty($_GET['search'])) {
        $search = sanitize($_GET['search']);
        // FULLTEXT search (much faster than LIKE %...%)
        $where[] = "MATCH(p.content) AGAINST(? IN BOOLEAN MODE)";
        $params[] = $search . '*';
    }
    
    // Filter by shipping company
    if (!empty($_GET['company'])) {
        $where[] = "u.shipping_company = ?";
        $params[] = sanitize($_GET['company']);
    }

    // Filter by province (normalize: strip Thành phố/Tỉnh/TP. prefix)
    if (!empty($_GET['province'])) {
        $prov = trim($_GET['province']);
        $stripped = preg_replace('/^(Thành phố |Tỉnh |TP\.\s*)/u', '', $prov);
        $where[] = "(p.province = ? OR p.province = ? OR p.province = ?)";
        $params[] = $prov;
        $params[] = $stripped;
        $params[] = "TP. " . $stripped;
    }
    // Filter by district (normalize: strip Quận/Huyện/Thị xã/Thành phố prefix)
    if (!empty($_GET['district'])) {
        $dist = trim($_GET['district']);
        $dStripped = preg_replace('/^(Quận |Huyện |Thị xã |Thành phố )/u', '', $dist);
        $where[] = "(p.district = ? OR p.district = ?)";
        $params[] = $dist;
        $params[] = $dStripped;
    }
    // Filter by ward
    if (!empty($_GET['ward'])) {
        $where[] = "p.ward = ?";
        $params[] = $_GET['ward'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    // === CACHE: Feed response (30s TTL, 0 DB queries on hit) ===
    $sort = $_GET['sort'] ?? 'new';
    $_cursor = intval($_GET['cursor'] ?? 0);
    $_cacheKey = 'feed_' . md5($_cursor . '_' . $whereClause . $sort . $page . $limit . json_encode($params));
    if ($method === 'GET' && !isset($_GET['id']) && !isset($_GET['action'])) {
        api_try_cache($_cacheKey, 30);
    }
    
    // Count total
    // Cursor-based pagination (faster for infinite scroll)
    $cursor = intval($_GET['cursor'] ?? 0);
    if ($cursor > 0) {
        $where[] = "p.id < ?";
        $params[] = $cursor;
        $whereClause = implode(' AND ', $where);
    }
    
    $total = $db->fetchOne("SELECT COUNT(*) as c FROM posts p LEFT JOIN users u ON p.user_id = u.id WHERE $whereClause", $params)['c'];
    
    $pagination = paginate($total, $page, $limit);
    
    // Get posts (use denormalized counts - no subqueries)
    $sql = "SELECT 
                p.*,
                u.fullname as user_name,
                u.avatar as user_avatar,
                u.username as user_username,
                u.shipping_company as shipping_company,
                sp.badge as sub_badge,
                sp.badge_color as sub_badge_color
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN user_subscriptions us2 ON us2.user_id = p.user_id AND us2.`status` = 'active' AND us2.expires_at > NOW()
            LEFT JOIN subscription_plans sp ON sp.id = us2.plan_id AND sp.price > 0
            WHERE $whereClause
            ORDER BY p.created_at DESC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
    
    $posts = $db->fetchAll($sql, $params);
    
    // Batch check liked/saved (2 queries instead of 40 N+1 queries)
    $authUid2 = getOptionalAuthUserId();
    if ($authUid2 && !empty($posts)) {
        $postIds = array_column($posts, 'id');
        $placeholders = implode(',', array_fill(0, count($postIds), '?'));
        
        $likedRows = $db->fetchAll("SELECT post_id FROM likes WHERE user_id = ? AND post_id IN ($placeholders)", array_merge([$authUid2], $postIds));
        $likedSet = array_flip(array_column($likedRows, 'post_id'));
        
        $savedRows = $db->fetchAll("SELECT post_id FROM saved_posts WHERE user_id = ? AND post_id IN ($placeholders)", array_merge([$authUid2], $postIds));
        $savedSet = array_flip(array_column($savedRows, 'post_id'));
        
        foreach ($posts as &$post) {
            $post['user_liked'] = isset($likedSet[$post['id']]);
            $post['user_saved'] = isset($savedSet[$post['id']]);
        }
    } else {
        foreach ($posts as &$post) {
            $post['user_liked'] = false;
            $post['user_saved'] = false;
        }
    }
    
    $feedResponse = ['success' => true, 'message' => 'Success', 'data' => [
        'posts' => $posts,
        'total' => $total,
        'total_pages' => $pagination['total_pages'],
        'page' => $pagination['current_page'],
        'per_page' => $pagination['per_page'],
        'next_cursor' => !empty($posts) ? intval($posts[count($posts)-1]['id']) : null
    ]];
    // Save to cache
    api_save_cache($_cacheKey, $feedResponse, 30);
    $json = json_encode($feedResponse, JSON_UNESCAPED_UNICODE);
    // ETag for 304
    $etag = '"' . md5($json) . '"';
    header('Content-Type: application/json; charset=utf-8');
    header('ETag: ' . $etag);
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304); exit;
    }
    echo $json;
    exit;
}

// ============================================
// CREATE POST
// ============================================
if ($method === 'POST') {
    // Rate limit: max 5 posts per minute per user
    $postUserId = getAuthUserId();

    $action = $_GET["action"] ?? null;
    if ($action) {
        require_once __DIR__ . "/auth-check.php";
        $userId = getAuthUserId();
        $input = json_decode(file_get_contents("php://input"), true);
        if ($action === "vote") {
            $postId = intval($input["post_id"] ?? 0);
            $voteType = $input["vote_type"] ?? "";
            // OPTIMIZED: toggle like in 1 check + 1 write + 1 update
            $ex = $db->fetchOne("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
            if ($voteType === "remove" || $ex) {
                $db->hardDelete("likes", "post_id = ? AND user_id = ?", [$postId, $userId]);
                $uv = null;
            } else {
                $db->insert("likes", ["post_id" => $postId, "user_id" => $userId]);
                $uv = true;
            }
            // Update count with subquery (1 query instead of SELECT + UPDATE)
            $db->query("UPDATE posts SET likes_count = (SELECT COUNT(*) FROM likes WHERE post_id = ?) WHERE id = ?", [$postId, $postId]);
            $score = intval($db->fetchOne("SELECT likes_count FROM posts WHERE id = ?", [$postId])['likes_count'] ?? 0);
            // Push: notify post owner on like (not unlike)
            if ($uv) { try { $post=$db->fetchOne("SELECT user_id FROM posts WHERE id=?",[$postId]); if($post&&intval($post['user_id'])!==$userId){ $me=$db->fetchOne("SELECT fullname FROM users WHERE id=?",[$userId]); asyncNotify(intval($post['user_id']),($me?$me['fullname']:'Ai đó').' đã thành công bài viết','Bài viết của bạn được thành công','post','/post-detail.html?id='.$postId); } } catch(Throwable $e){} }
            api_cache_flush("feed_"); success("OK", ["score" => intval($score), "user_vote" => $uv ? "up" : null]);
        }
        if ($action === "comment") { $pid = intval($input["post_id"] ?? 0); $ct = sanitize($input["content"] ?? ""); $par = $input["parent_id"] ?? null; if (empty($ct)) error("Nội dung trống"); $db->insert("comments", ["post_id" => $pid, "user_id" => $userId, "parent_id" => $par, "content" => $ct]); $db->query("UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?", [$pid]); try{$post=$db->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);if($post&&intval($post['user_id'])!==$userId){$me=$db->fetchOne("SELECT fullname FROM users WHERE id=?",[$userId]);asyncNotify(intval($post['user_id']),($me?$me['fullname']:'Ai đó').' đã ghi chú',mb_substr($ct,0,50),'post','/post-detail.html?id='.$pid);}}catch(Throwable $e){} try{awardXP($userId,"comment",5,"Ghi chú bài #".$pid);}catch(Throwable$e){} api_cache_flush("feed_"); success("Đã ghi chú!"); }
        if ($action === "save") { $pid = intval($input["post_id"] ?? 0); $ex = $db->fetchOne("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?", [$pid, $userId]); if ($ex) { $db->hardDelete("saved_posts", "post_id = ? AND user_id = ?", [$pid, $userId]); success("OK", ["saved" => false]); } else { $db->insert("saved_posts", ["post_id" => $pid, "user_id" => $userId]); success("OK", ["saved" => true]); } }
        if ($action === "share") { $pid = intval($input["post_id"] ?? 0); if ($pid) { $db->query("UPDATE posts SET shares_count = shares_count + 1 WHERE id = ?", [$pid]); $cnt = $db->fetchOne("SELECT shares_count FROM posts WHERE id = ?", [$pid]); try{$post=$db->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);if($post&&intval($post['user_id'])!==$userId){$me=$db->fetchOne("SELECT fullname FROM users WHERE id=?",[$userId]);asyncNotify(intval($post['user_id']),($me?$me['fullname']:'Ai đó').' đã chuyển tiếp','Bài viết được chia sẻ','post','/post-detail.html?id='.$pid);}}catch(Throwable $e){} api_cache_flush("feed_"); success("OK", ["shares_count" => intval($cnt['shares_count'] ?? 0)]); } else { success("OK"); } }
        if ($action === "delete") { $pid = intval($input["post_id"] ?? 0); $po = $db->fetchOne("SELECT user_id FROM posts WHERE id = ?", [$pid]); if (!$po || intval($po["user_id"]) !== $userId) error("Không có quyền"); $db->update("posts", ["status" => "deleted"], "id = ?", [$pid]); api_cache_flush("feed_"); success("Đã xóa"); }
        if ($action === "comments") { $pid = intval($_GET["post_id"] ?? 0); $cmts = $db->fetchAll("SELECT c.*, u.fullname as user_name, u.avatar as user_avatar FROM comments c LEFT JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.status = 'active' ORDER BY c.created_at ASC", [$pid]); success("OK", $cmts); }
        if ($action === "vote_comment") {
            $cid = intval($input["comment_id"] ?? 0);
            if (!$cid) error("Missing comment_id");
            // Check if already liked
            $existing = $db->fetchOne("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?", [$cid, $userId]);
            if ($existing) {
                $db->query("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?", [$cid, $userId]);
                $db->query("UPDATE comments SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?", [$cid]);
                $cnt = $db->fetchOne("SELECT likes_count FROM comments WHERE id = ?", [$cid]);
                success("OK", ["user_vote" => null, "score" => intval($cnt["likes_count"] ?? 0)]);
            } else {
                $db->query("INSERT IGNORE INTO comment_likes(comment_id,user_id) VALUES(?,?)", [$cid, $userId]);
                $db->query("UPDATE comments SET likes_count = likes_count + 1 WHERE id = ?", [$cid]);
                $cnt = $db->fetchOne("SELECT likes_count FROM comments WHERE id = ?", [$cid]);
                success("OK", ["user_vote" => "up", "score" => intval($cnt["likes_count"] ?? 0)]);
            }
        }
        exit;
    }
    require_once __DIR__ . "/auth-check.php";
    $userId = getAuthUserId();

    // Feature gate: check daily post limit
    $limitErr = checkLimit($userId, 'posts_per_day');
    if ($limitErr) { error($limitErr, 403); }

    // Handle both JSON and form-data
    $jsonInput = json_decode(file_get_contents("php://input"), true);
    $content = $jsonInput["content"] ?? ($_POST["content"] ?? "");
    $content = sanitize($content);
    $imagesJson = null;

    // Get other fields from JSON or POST
    $title = $jsonInput["title"] ?? ($_POST["title"] ?? null);
    $type = $jsonInput["type"] ?? ($_POST["type"] ?? 'post');
    $province = $jsonInput["province"] ?? ($_POST["province"] ?? null);
    $isAnonymous = $jsonInput["is_anonymous"] ?? ($_POST["is_anonymous"] ?? 0);
    $videoUrl = $jsonInput["video_url"] ?? ($_POST["video_url"] ?? null);

    if (empty($content) && empty($jsonInput['images']) && empty($_FILES['image']) && empty($_FILES['images'])) {
        error('Vui lòng nhập nội dung hoặc chọn ảnh');
    }

    // Handle base64 images from JSON
    if (!empty($jsonInput['images']) && is_array($jsonInput['images'])) {
        $savedImages = [];
        $uploadDir = __DIR__ . '/../uploads/posts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        foreach ($jsonInput['images'] as $base64) {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64, $m)) {
                $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
                $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $base64));
                $filename = uniqid('post_') . '.' . $ext;
                file_put_contents($uploadDir . $filename, $data);
                optimizeImage($uploadDir . $filename, $uploadDir . $filename, 1200, 82);
                $savedImages[] = '/uploads/posts/' . $filename;
            }
        }
        if (!empty($savedImages)) $imagesJson = json_encode($savedImages);
    }

    // Handle multiple file uploads: images[] (from FormData)
    if (!empty($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $savedImages = [];
        $uploadDir = __DIR__ . '/../uploads/posts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (!in_array($ext, $allowed)) continue;
            if ($_FILES['images']['size'][$i] > 10 * 1024 * 1024) continue;
            $filename = uniqid('post_') . '.' . $ext;
            move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $filename);
            optimizeImage($uploadDir . $filename, $uploadDir . $filename, 1200, 82);
            $savedImages[] = '/uploads/posts/' . $filename;
        }
        if (!empty($savedImages)) $imagesJson = json_encode($savedImages);
    }

    // Handle single file upload: image (legacy fallback)
    if (!$imagesJson && !empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['image'], 'posts');
        if ($uploadResult['success']) {
            $imagesJson = json_encode([$uploadResult['url']]);
        } else {
            error($uploadResult['message']);
        }
    }

    // Handle video file upload
    if (!empty($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $vExt = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $vAllowed = ['mp4','mov','avi','webm'];
        if (in_array($vExt, $vAllowed) && $_FILES['video']['size'] <= 50 * 1024 * 1024) {
            $vDir = __DIR__ . '/../uploads/videos/';
            if (!is_dir($vDir)) mkdir($vDir, 0755, true);
            $vName = uniqid('vid_') . '.' . $vExt;
            move_uploaded_file($_FILES['video']['tmp_name'], $vDir . $vName);
            $videoUrl = '/uploads/videos/' . $vName;
        }
    }

    try {
        // Create post
        $province = $jsonInput['province'] ?? ($_POST['province'] ?? null);
        $district = $jsonInput['district'] ?? ($_POST['district'] ?? null);
        $ward = $jsonInput['ward'] ?? ($_POST['ward'] ?? null);
        
        $postId = $db->insert('posts', [
            'user_id' => $userId,
            'content' => $content,
            'images' => $imagesJson,
            'type' => $type,
            'status' => 'active',
            'video_url' => $videoUrl,
            'province' => $province,
            'district' => $district,
            'ward' => $ward
        ]);
        
        // Extract and save hashtags (optional, won't break post if fails)
        try {
            preg_match_all('/#(\w+)/u', $content, $hashtags);
            if (!empty($hashtags[1])) {
                foreach (array_unique($hashtags[1]) as $tag) {
                    $db->insert('hashtags', ['tag' => strtolower($tag), 'post_id' => $postId, 'created_at' => date('Y-m-d H:i:s')]);
                }
            }
        } catch (Exception $he) { error_log('Hashtag save error: ' . $he->getMessage()); }
        
        try{awardXP($userId,'post',10,'Đăng bài mới');}catch(Throwable$e){} success('Đăng bài thành công!', [
            'post_id' => $postId,
            'images' => $imagesJson,
            'type' => $type
        ]);
        
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error('Đăng bài thất bại: ' . $e->getMessage(), 500);
        } else {
            error('Đăng bài thất bại. Vui lòng thử lại', 500);
        }
    }
}

// ============================================
// UPDATE POST
// ============================================

if ($method === 'PUT') {
    requireAuth();
    
    $input = getJsonInput();
    $postId = intval($input['post_id'] ?? 0);
    $userId = getAuthUserId();
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    // Check ownership
    $post = $db->fetchOne("SELECT * FROM posts WHERE id = ? AND user_id = ?", [$postId, $userId]);
    
    if (!$post) {
        error('Bạn không có quyền sửa bài viết này', 403);
    }
    
    // Update content
    if (isset($input['content'])) {
        $db->update('posts',
            ['content' => sanitize($input['content'])],
            'id = ?',
            [$postId]
        );
    }
    
    success('Cập nhật bài viết thành công');
}

// ============================================
// DELETE POST
// ============================================

if ($method === 'DELETE') {
    requireAuth();
    
    $postId = intval($_GET['id'] ?? 0);
    $userId = getAuthUserId();
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    // Check ownership or admin
    $post = $db->fetchOne("SELECT * FROM posts WHERE id = ?", [$postId]);
    
    if (!$post) {
        error('Bài viết không tồn tại', 404);
    }
    
    if ($post['user_id'] != $userId && !isAdmin()) {
        error('Bạn không có quyền xóa bài viết này', 403);
    }
    
    // Soft delete
    $db->update('posts',
        ['status' => 'deleted'],
        'id = ?',
        [$postId]
    );
    
    // Delete image if exists
    if ($post['image_url']) {
        $imagePath = str_replace(SITE_URL . '/uploads/', '', $post['image_url']);
        deleteFile($imagePath);
    }
    
    success('Xóa bài viết thành công');
}

// Method not allowed
error('Method not allowed', 405);
