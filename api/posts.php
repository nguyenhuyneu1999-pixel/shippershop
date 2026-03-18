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
require_once __DIR__ . '/auth-check.php';

// Set CORS headers
setCorsHeaders();

// Get database
$db = db();
try{$db->query("ALTER TABLE comments ADD COLUMN likes_count INT DEFAULT 0");}catch(\Exception $e){}

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
        $user = $db->fetchOne("SELECT id, fullname, username, avatar, email FROM users WHERE id = ?", [$uid]);
        if (!$user) error("User not found");
        $user['posts_count'] = $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND status='active'", [$uid])['c'] ?? 0;
        $user['likes_count'] = $db->fetchOne("SELECT COUNT(*) as c FROM post_likes WHERE user_id = ?", [$uid])['c'] ?? 0;
        $user['comments_count'] = $db->fetchOne("SELECT COUNT(*) as c FROM comments WHERE user_id = ? AND status='active'", [$uid])['c'] ?? 0;
        $user['is_following'] = false;
        if ($userId) {
            $user['is_following'] = (bool)$db->fetchOne("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?", [$userId, $uid]);
        }
        success("OK", ["user" => $user]);
    }

    if ($getAction === "user_posts") {
        $uid = intval($_GET["user_id"] ?? 0);
        $tab = $_GET["tab"] ?? "posts";
        if ($tab === "posts") {
            $items = $db->fetchAll("SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company as shipping_company, (SELECT COUNT(*) FROM post_likes WHERE post_id=p.id) as likes_count, (SELECT COUNT(*) FROM comments WHERE post_id=p.id AND status='active') as comments_count FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.user_id=? AND p.status='active' ORDER BY p.created_at DESC LIMIT 50", [$uid]);
        } elseif ($tab === "liked") {
            $items = $db->fetchAll("SELECT p.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company as shipping_company, (SELECT COUNT(*) FROM post_likes WHERE post_id=p.id) as likes_count, (SELECT COUNT(*) FROM comments WHERE post_id=p.id AND status='active') as comments_count FROM posts p LEFT JOIN users u ON p.user_id=u.id JOIN post_likes pl ON pl.post_id=p.id WHERE pl.user_id=? AND p.status='active' ORDER BY pl.created_at DESC LIMIT 50", [$uid]);
        } else {
            $items = $db->fetchAll("SELECT DISTINCT p.*, u.fullname as user_name, u.avatar as user_avatar, u.shipping_company as shipping_company, (SELECT COUNT(*) FROM post_likes WHERE post_id=p.id) as likes_count, (SELECT COUNT(*) FROM comments WHERE post_id=p.id AND status='active') as comments_count FROM posts p LEFT JOIN users u ON p.user_id=u.id JOIN comments c ON c.post_id=p.id WHERE c.user_id=? AND p.status='active' ORDER BY p.created_at DESC LIMIT 50", [$uid]);
        }
        success("OK", $items);
    }

    if ($getAction === "comments") {
        $pid = intval($_GET["post_id"] ?? 0);
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
                    u.shipping_company as shipping_company,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count
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
        $where[] = "p.content LIKE ?";
        $params[] = "%$search%";
    }
    
    // Filter by province
    if (!empty($_GET['province'])) {
        $where[] = "p.province = ?";
        $params[] = $_GET['province'];
    }
    // Filter by district
    if (!empty($_GET['district'])) {
        $where[] = "p.district = ?";
        $params[] = $_GET['district'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Count total
    $total = $db->fetchColumn(
        "SELECT COUNT(*) FROM posts p WHERE $whereClause",
        $params
    );
    
    $pagination = paginate($total, $page, $limit);
    
    // Get posts
    $sql = "SELECT 
                p.*,
                u.fullname as user_name,
                u.avatar as user_avatar,
                u.username as user_username,
                u.shipping_company as shipping_company,
                (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE $whereClause
            ORDER BY p.created_at DESC
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
    
    $posts = $db->fetchAll($sql, $params);
    
    $authUid2 = getOptionalAuthUserId();
    if ($authUid2) {
        foreach ($posts as &$post) {
            $liked = $db->fetchOne("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", [$post['id'], $authUid2]);
            $post['user_liked'] = $liked ? true : false;
            $saved = $db->fetchOne("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?", [$post['id'], $authUid2]);
            $post['user_saved'] = $saved ? true : false;
        }
    } else {
        foreach ($posts as &$post) {
            $post['user_liked'] = false;
            $post['user_saved'] = false;
        }
    }
    
    success('Success', ['posts' => $posts]);
}

// ============================================
// CREATE POST
// ============================================
if ($method === 'POST') {

    $action = $_GET["action"] ?? null;
    if ($action) {
        require_once __DIR__ . "/auth-check.php";
        $userId = getAuthUserId();
        $input = json_decode(file_get_contents("php://input"), true);
        if ($action === "vote") {
            $postId = intval($input["post_id"] ?? 0);
            $voteType = $input["vote_type"] ?? "";
            if ($voteType === "remove") { $db->hardDelete("likes", "post_id = ? AND user_id = ?", [$postId, $userId]); }
            else { $ex = $db->fetchOne("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", [$postId, $userId]); if ($ex) { $db->hardDelete("likes", "post_id = ? AND user_id = ?", [$postId, $userId]); } else { $db->insert("likes", ["post_id" => $postId, "user_id" => $userId]); } }
            $score = $db->fetchColumn("SELECT COUNT(*) FROM likes WHERE post_id = ?", [$postId]);
            $uv = $db->fetchOne("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", [$postId, $userId]);
            success("OK", ["score" => intval($score), "user_vote" => $uv ? "up" : null]);
        }
        if ($action === "comment") { $pid = intval($input["post_id"] ?? 0); $ct = sanitize($input["content"] ?? ""); $par = $input["parent_id"] ?? null; if (empty($ct)) error("Nội dung trống"); $db->insert("comments", ["post_id" => $pid, "user_id" => $userId, "parent_id" => $par, "content" => $ct]); success("Đã bình luận!"); }
        if ($action === "save") { $pid = intval($input["post_id"] ?? 0); $ex = $db->fetchOne("SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?", [$pid, $userId]); if ($ex) { $db->hardDelete("saved_posts", "post_id = ? AND user_id = ?", [$pid, $userId]); success("OK", ["saved" => false]); } else { $db->insert("saved_posts", ["post_id" => $pid, "user_id" => $userId]); success("OK", ["saved" => true]); } }
        if ($action === "share") { success("OK"); }
        if ($action === "delete") { $pid = intval($input["post_id"] ?? 0); $po = $db->fetchOne("SELECT user_id FROM posts WHERE id = ?", [$pid]); if (!$po || intval($po["user_id"]) !== $userId) error("Không có quyền"); $db->update("posts", ["status" => "deleted"], "id = ?", [$pid]); success("Đã xóa"); }
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
                $db->query("CREATE TABLE IF NOT EXISTS comment_likes(id INT AUTO_INCREMENT PRIMARY KEY,comment_id INT,user_id INT,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,UNIQUE KEY(comment_id,user_id))ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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

    // Handle both JSON and form-data
    $jsonInput = json_decode(file_get_contents("php://input"), true);
    $content = $jsonInput["content"] ?? ($_POST["content"] ?? "");
    $content = sanitize($content);
    $imagesJson = null;

    // Get other fields from JSON
    $title = $jsonInput["title"] ?? null;
    $type = $jsonInput["type"] ?? 'post';
    $province = $jsonInput["province"] ?? null;
    $isAnonymous = $jsonInput["is_anonymous"] ?? 0;
    $videoUrl = $jsonInput["video_url"] ?? null;

    if (empty($content) && empty($jsonInput['images']) && empty($_FILES['image'])) {
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
                $savedImages[] = '/uploads/posts/' . $filename;
            }
        }
        if (!empty($savedImages)) $imagesJson = json_encode($savedImages);
    }

    // Handle file upload (form-data fallback)
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['image'], 'posts');
        if ($uploadResult['success']) {
            $imagesJson = json_encode([$uploadResult['url']]);
        } else {
            error($uploadResult['message']);
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
        
        success('Đăng bài thành công!', [
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
    $userId = getCurrentUserId();
    
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
    $userId = getCurrentUserId();
    
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
