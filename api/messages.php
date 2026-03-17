<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function ok($msg, $data = []) { echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function err($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
function auth() {
    if (isset($_SESSION['user_id'])) return $_SESSION['user_id'];
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(.+)/i', $h, $m)) {
        $t = verifyJWT($m[1]);
        if ($t) return $t['user_id'];
    }
    return null;
}

$db = db();
$uid = auth();
if (!$uid) err('Vui lòng đăng nhập', 401);
$action = $_GET['action'] ?? '';

// GET CONVERSATIONS
if ($action === 'conversations') {
    $convs = $db->fetchAll(
        "SELECT c.*,
         CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END as other_id,
         CASE WHEN c.user1_id = ? THEN u2.fullname ELSE u1.fullname END as other_name,
         CASE WHEN c.user1_id = ? THEN u2.avatar ELSE u1.avatar END as other_avatar,
         CASE WHEN c.user1_id = ? THEN u2.username ELSE u1.username END as other_username,
         (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.read_at IS NULL) as unread
         FROM conversations c
         JOIN users u1 ON c.user1_id = u1.id
         JOIN users u2 ON c.user2_id = u2.id
         WHERE c.user1_id = ? OR c.user2_id = ?
         ORDER BY c.last_message_at DESC",
        [$uid, $uid, $uid, $uid, $uid, $uid, $uid]
    );
    ok('OK', $convs);
}

// GET MESSAGES
if ($action === 'messages') {
    $convId = intval($_GET['conversation_id'] ?? 0);
    if (!$convId) err('Thiếu conversation_id');
    // Verify user is part of conversation
    $conv = $db->fetchOne("SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)", [$convId, $uid, $uid]);
    if (!$conv) err('Không có quyền', 403);
    // Mark as read
    $db->query("UPDATE messages SET read_at = NOW() WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL", [$convId, $uid]);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;
    $msgs = $db->fetchAll(
        "SELECT m.*, u.fullname as sender_name, u.avatar as sender_avatar
         FROM messages m JOIN users u ON m.sender_id = u.id
         WHERE m.conversation_id = ?
         ORDER BY m.created_at DESC LIMIT $limit OFFSET $offset",
        [$convId]
    );
    ok('OK', ['messages' => array_reverse($msgs), 'conversation' => $conv]);
}

// SEND MESSAGE
if ($action === 'send') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    $toUserId = intval($input['to_user_id'] ?? 0);
    $convId = intval($input['conversation_id'] ?? 0);
    $content = trim($input['content'] ?? '');
    if (empty($content) && empty($_FILES['image'])) err('Tin nhắn trống');

    // Find or create conversation
    if (!$convId && $toUserId) {
        $u1 = min($uid, $toUserId); $u2 = max($uid, $toUserId);
        $conv = $db->fetchOne("SELECT * FROM conversations WHERE user1_id = ? AND user2_id = ?", [$u1, $u2]);
        if ($conv) { $convId = $conv['id']; }
        else {
            $convId = $db->insert('conversations', ['user1_id'=>$u1, 'user2_id'=>$u2, 'last_message'=>$content, 'last_message_at'=>date('Y-m-d H:i:s')]);
        }
    }
    if (!$convId) err('Thiếu thông tin người nhận');

    // Verify access
    $conv = $db->fetchOne("SELECT * FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)", [$convId, $uid, $uid]);
    if (!$conv) err('Không có quyền');

    $image = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fname = 'msg_' . $uid . '_' . time() . '.' . $ext;
            $dir = __DIR__ . '/../uploads/messages/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname)) {
                $image = '/uploads/messages/' . $fname;
            }
        }
    }

    $msgId = $db->insert('messages', [
        'conversation_id'=>$convId, 'sender_id'=>$uid, 'content'=>$content, 'image'=>$image, 'created_at'=>date('Y-m-d H:i:s')
    ]);
    $db->query("UPDATE conversations SET last_message = ?, last_message_at = NOW() WHERE id = ?", [($content ?: '[Hình ảnh]'), $convId]);
    ok('OK', ['message_id'=>$msgId, 'conversation_id'=>$convId, 'image'=>$image]);
}

// UNREAD COUNT
if ($action === 'unread') {
    $count = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM messages m JOIN conversations c ON m.conversation_id = c.id
         WHERE (c.user1_id = ? OR c.user2_id = ?) AND m.sender_id != ? AND m.read_at IS NULL",
        [$uid, $uid, $uid]
    )['cnt'];
    ok('OK', ['count' => intval($count)]);
}

// START CONVERSATION (from marketplace or profile)
if ($action === 'start') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    $toUserId = intval($input['to_user_id'] ?? 0);
    if (!$toUserId || $toUserId === $uid) err('Không hợp lệ');
    $u1 = min($uid, $toUserId); $u2 = max($uid, $toUserId);
    $conv = $db->fetchOne("SELECT * FROM conversations WHERE user1_id = ? AND user2_id = ?", [$u1, $u2]);
    if ($conv) { ok('OK', ['conversation_id' => $conv['id']]); }
    $convId = $db->insert('conversations', ['user1_id'=>$u1, 'user2_id'=>$u2, 'created_at'=>date('Y-m-d H:i:s')]);
    ok('OK', ['conversation_id' => $convId]);
}

err('Action không hợp lệ');