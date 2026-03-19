<?php
/**
 * ============================================
 * SOCIAL API
 * ============================================
 * 
 * Endpoints:
 * POST /api/social.php?action=like - Toggle like on post
 * POST /api/social.php?action=comment - Add comment to post
 * GET /api/social.php?action=comments&post_id=1 - Get comments
 * POST /api/social.php?action=follow - Follow/unfollow user
 * GET /api/social.php?action=followers&user_id=1 - Get followers
 * GET /api/social.php?action=following&user_id=1 - Get following
 * GET /api/social.php?action=feed - Get feed
 * POST /api/social.php?action=save - Save post
 * GET /api/social.php?action=saved_posts - Get saved posts
 * GET /api/social.php?action=stats&user_id=1 - Get user stats
 * DELETE /api/social.php?action=delete_comment&comment_id=1 - Delete comment
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

// Get action
$action = $_GET['action'] ?? '';

// ============================================
// TOGGLE LIKE
// ============================================

if ($action === 'like') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    $userId = getAuthUserId();
    $postId = intval($input['post_id'] ?? 0);
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    // Check if post exists
    $post = $db->fetchOne("SELECT id FROM posts WHERE id = ? AND status = 'active'", [$postId]);
    
    if (!$post) {
        error('Bài viết không tồn tại', 404);
    }
    
    // Check if already liked
    $existing = $db->fetchOne(
        "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?",
        [$postId, $userId]
    );
    
    if ($existing) {
        // Unlike
        $db->hardDelete('post_likes', 'post_id = ? AND user_id = ?', [$postId, $userId]);
        
        success('Đã bỏ thích', [
            'is_liked' => false,
            'post_id' => $postId
        ]);
    } else {
        // Like
        $db->insert('post_likes', [
            'post_id' => $postId,
            'user_id' => $userId
        ]);
        
        success('Đã thích', [
            'is_liked' => true,
            'post_id' => $postId
        ]);
    }
}

// ============================================
// ADD COMMENT
// ============================================

if ($action === 'comment') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    $userId = getAuthUserId();
    $postId = intval($input['post_id'] ?? 0);
    $content = sanitize($input['content'] ?? '');
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    if (empty($content)) {
        error('Vui lòng nhập nội dung bình luận');
    }
    
    // Check if post exists
    $post = $db->fetchOne("SELECT id FROM posts WHERE id = ? AND status = 'active'", [$postId]);
    
    if (!$post) {
        error('Bài viết không tồn tại', 404);
    }
    
    // Create comment
    $commentId = $db->insert('comments', [
        'post_id' => $postId,
        'user_id' => $userId,
        'content' => $content,
        'status' => 'active'
    ]);
    
    success('Đã bình luận', [
        'comment_id' => $commentId,
        'post_id' => $postId
    ]);
}

// ============================================
// GET COMMENTS
// ============================================

if ($action === 'comments') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $postId = intval($_GET['post_id'] ?? 0);
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    // Get comments with user info
    $sql = "SELECT 
                c.*,
                u.fullname as user_name,
                u.avatar as user_avatar
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.post_id = ? AND c.status = 'active'
            ORDER BY c.created_at ASC";
    
    $comments = $db->fetchAll($sql, [$postId]);
    
    // Nếu không có bình luận, trả về mảng rỗng
    if (empty($comments)) {
        success('Success', []);
    }
    
    success('Success', $comments);
}

// ============================================
// DELETE COMMENT
// ============================================

if ($action === 'delete_comment') {
    if (getRequestMethod() !== 'DELETE') {
        error('Method not allowed', 405);
    }
    
    $commentId = intval($_GET['comment_id'] ?? 0);
    $userId = getAuthUserId();
    
    if ($commentId <= 0) {
        error('Comment ID không hợp lệ');
    }
    
    // Check ownership or admin
    $comment = $db->fetchOne("SELECT * FROM comments WHERE id = ?", [$commentId]);
    
    if (!$comment) {
        error('Bình luận không tồn tại', 404);
    }
    
    if ($comment['user_id'] != $userId && !isAdmin()) {
        error('Bạn không có quyền xóa bình luận này', 403);
    }
    
    // Soft delete
    $db->update('comments',
        ['status' => 'deleted'],
        'id = ?',
        [$commentId]
    );
    
    success('Đã xóa bình luận');
}

// ============================================
// FOLLOW/UNFOLLOW USER
// ============================================

if ($action === 'follow') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    $userId = getAuthUserId();
    $followUserId = intval($input['user_id'] ?? 0);
    
    if ($followUserId <= 0) {
        error('User ID không hợp lệ');
    }
    
    if ($userId === $followUserId) {
        error('Không thể theo dõi chính mình');
    }
    
    // Check if user exists
    $targetUser = $db->fetchOne("SELECT id FROM users WHERE id = ? AND status = 'active'", [$followUserId]);
    
    if (!$targetUser) {
        error('User không tồn tại', 404);
    }
    
    // Check if already following
    $existing = $db->fetchOne(
        "SELECT id FROM follows WHERE follower_id = ? AND following_id = ?",
        [$userId, $followUserId]
    );
    
    if ($existing) {
        // Unfollow
        $db->hardDelete('follows', 'follower_id = ? AND following_id = ?', [$userId, $followUserId]);
        
        success('Đã bỏ theo dõi', [
            'is_following' => false,
            'user_id' => $followUserId
        ]);
    } else {
        // Follow
        $db->insert('follows', [
            'follower_id' => $userId,
            'following_id' => $followUserId
        ]);
        
        // Push: notify user they got a new follower
        try {
            require_once __DIR__.'/../includes/push-helper.php';
            $me = $db->fetchOne("SELECT fullname FROM users WHERE id = ?", [$userId]);
            $mName = $me ? $me['fullname'] : 'Ai đó';
            notifyUser($followUserId, $mName . ' đã theo dõi bạn', 'Bạn có người theo dõi mới', 'social', '/user.html?id=' . $userId);
        } catch (Throwable $e) {}
        
        success('Đã theo dõi', [
            'is_following' => true,
            'user_id' => $followUserId
        ]);
    }
}

// ============================================
// GET FOLLOWERS
// ============================================

if ($action === 'followers') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $userId = intval($_GET['user_id'] ?? 0);
    
    if ($userId <= 0) {
        error('User ID không hợp lệ');
    }
    
    // Get followers
    $sql = "SELECT 
                u.id,
                u.fullname,
                u.email,
                u.avatar,
                u.bio,
                f.created_at as followed_at
            FROM follows f
            LEFT JOIN users u ON f.follower_id = u.id
            WHERE f.following_id = ? AND u.status = 'active'
            ORDER BY f.created_at DESC";
    
    $followers = $db->fetchAll($sql, [$userId]);
    
    success('Success', $followers);
}

// ============================================
// GET FOLLOWING
// ============================================

if ($action === 'following') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $userId = intval($_GET['user_id'] ?? 0);
    
    if ($userId <= 0) {
        error('User ID không hợp lệ');
    }
    
    // Get following
    $sql = "SELECT 
                u.id,
                u.fullname,
                u.email,
                u.avatar,
                u.bio,
                f.created_at as followed_at
            FROM follows f
            LEFT JOIN users u ON f.following_id = u.id
            WHERE f.follower_id = ? AND u.status = 'active'
            ORDER BY f.created_at DESC";
    
    $following = $db->fetchAll($sql, [$userId]);
    
    success('Success', $following);
}

// ============================================
// GET USER STATS
// ============================================

if ($action === 'stats') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    
    $userId = intval($_GET['user_id'] ?? 0);
    
    if ($userId <= 0) {
        error('User ID không hợp lệ');
    }
    
    // Get stats
    $stats = [
        'followers_count' => $db->fetchOne("SELECT COUNT(*) as c FROM follows WHERE following_id = ?", [$userId])['c'],
        'following_count' => $db->fetchOne("SELECT COUNT(*) as c FROM follows WHERE follower_id = ?", [$userId])['c'],
        'posts_count' => $db->fetchOne("SELECT COUNT(*) as c FROM posts WHERE user_id = ? AND status = ?", [$userId, 'active'])['c'],
        'total_likes' => $db->fetchOne("SELECT COUNT(*) as c FROM post_likes pl 
             JOIN posts p ON pl.post_id = p.id 
             WHERE p.user_id = ?", [$userId])['c'] ?? 0
    ];
    
    success('Success', $stats);
}

// ============================================
// GET FEED (Following users' posts)
// ============================================

if ($action === 'feed') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    $userId = getAuthUserId();
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;
    
    // Get posts from users that current user follows
    $sql = "SELECT 
                p.*,
                u.fullname as user_name,
                u.avatar as user_avatar,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count,
                EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.user_id IN (
                SELECT following_id FROM follows WHERE follower_id = ?
            )
            AND p.status = 'active'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    
    $offset = ($page - 1) * $limit;
    $posts = $db->fetchAll($sql, [$userId, $userId, $limit, $offset]);
    
    success('Success', $posts);
}

// ============================================
// SAVE/BOOKMARK POST
// ============================================

if ($action === 'save') {
    if (getRequestMethod() !== 'POST') {
        error('Method not allowed', 405);
    }
    
    $input = getJsonInput();
    $userId = getAuthUserId();
    $postId = intval($input['post_id'] ?? 0);
    
    if ($postId <= 0) {
        error('Post ID không hợp lệ');
    }
    
    // Check if post exists
    $post = $db->fetchOne("SELECT id FROM posts WHERE id = ? AND status = 'active'", [$postId]);
    
    if (!$post) {
        error('Bài viết không tồn tại', 404);
    }
    
    // Check if already saved
    $existing = $db->fetchOne(
        "SELECT id FROM saved_posts WHERE post_id = ? AND user_id = ?",
        [$postId, $userId]
    );
    
    if ($existing) {
        // Unsave
        $db->hardDelete('saved_posts', 'post_id = ? AND user_id = ?', [$postId, $userId]);
        
        success('Đã bỏ lưu', [
            'is_saved' => false,
            'post_id' => $postId
        ]);
    } else {
        // Save
        $db->insert('saved_posts', [
            'post_id' => $postId,
            'user_id' => $userId
        ]);
        
        success('Đã lưu bài viết', [
            'is_saved' => true,
            'post_id' => $postId
        ]);
    }
}

// ============================================
// GET SAVED POSTS
// ============================================

if ($action === 'saved_posts') {
    if (getRequestMethod() !== 'GET') {
        error('Method not allowed', 405);
    }
    $userId = getAuthUserId();
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;
    
    // Get saved posts
    $sql = "SELECT 
                p.*,
                u.fullname as user_name,
                u.avatar as user_avatar,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND status = 'active') as comments_count,
                EXISTS(SELECT 1 FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked,
                sp.created_at as saved_at
            FROM saved_posts sp
            LEFT JOIN posts p ON sp.post_id = p.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE sp.user_id = ? AND p.status = 'active'
            ORDER BY sp.created_at DESC
            LIMIT ? OFFSET ?";
    
    $offset = ($page - 1) * $limit;
    $posts = $db->fetchAll($sql, [$userId, $userId, $limit, $offset]);
    
    success('Success', $posts);
}

// Invalid action
error('Invalid action', 400);


// LIKE COMMENT
if ($action === 'like_comment') {
    $uid = getAuthUserId();
    if (!$uid) { echo json_encode(['success'=>false,'message'=>'Login required']); exit; }
    $input = json_decode(file_get_contents('php://input'), true);
    $cid = intval($input['comment_id'] ?? 0);
    if (!$cid) { echo json_encode(['success'=>false,'message'=>'Missing comment_id']); exit; }
    $db = db();
    $exists = $db->fetchOne("SELECT id FROM comment_likes WHERE comment_id=? AND user_id=?", [$cid, $uid]);
    if ($exists) {
        $db->query("DELETE FROM comment_likes WHERE comment_id=? AND user_id=?", [$cid, $uid]);
        $db->query("UPDATE post_comments SET likes_count = GREATEST(0, likes_count - 1) WHERE id=?", [$cid]);
        $count = $db->fetchOne("SELECT likes_count FROM post_comments WHERE id=?", [$cid])['likes_count'] ?? 0;
        echo json_encode(['success'=>true,'data'=>['liked'=>false,'count'=>intval($count)]]); exit;
    } else {
        $db->query("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)", [$cid, $uid]);
        $db->query("UPDATE post_comments SET likes_count = likes_count + 1 WHERE id=?", [$cid]);
        $count = $db->fetchOne("SELECT likes_count FROM post_comments WHERE id=?", [$cid])['likes_count'] ?? 0;
        echo json_encode(['success'=>true,'data'=>['liked'=>true,'count'=>intval($count)]]); exit;
    }
}