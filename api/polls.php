<?php
/**
 * ShipperShop Poll API
 * POST /api/polls.php?action=create — Create poll on a post
 * POST /api/polls.php?action=vote — Vote on poll option
 * GET  /api/polls.php?action=results&post_id=X — Poll results
 */
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth-check.php';
require_once __DIR__ . '/../includes/api-cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$d = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get poll results
if ($method === 'GET' && $action === 'results') {
    $postId = intval($_GET['post_id'] ?? 0);
    if (!$postId) { echo json_encode(['success' => false, 'message' => 'Missing post_id']); exit; }
    
    api_try_cache('poll_' . $postId, 10);
    
    $poll = $d->fetchOne("SELECT * FROM post_polls WHERE post_id = ?", [$postId]);
    if (!$poll) { echo json_encode(['success' => false, 'message' => 'No poll']); exit; }
    
    $options = $d->fetchAll("SELECT id, text, vote_count FROM poll_options WHERE poll_id = ? ORDER BY id", [$poll['id']]);
    $totalVotes = 0;
    foreach ($options ?: [] as $o) $totalVotes += intval($o['vote_count']);
    
    $userId = getOptionalAuthUserId();
    $userVote = null;
    if ($userId) {
        $v = $d->fetchOne("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?", [$poll['id'], $userId]);
        if ($v) $userVote = intval($v['option_id']);
    }
    
    $expired = $poll['expires_at'] && strtotime($poll['expires_at']) < time();
    
    success('OK', [
        'poll_id' => intval($poll['id']),
        'question' => $poll['question'],
        'options' => $options ?: [],
        'total_votes' => $totalVotes,
        'user_vote' => $userVote,
        'expired' => $expired,
        'expires_at' => $poll['expires_at']
    ]);
}

// Create poll
if ($method === 'POST' && $action === 'create') {
    $userId = getAuthUserId();
    if (!$userId) { error('Auth required', 401); }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['post_id'] ?? 0);
    $question = trim($input['question'] ?? '');
    $options = $input['options'] ?? [];
    $expiresIn = intval($input['expires_hours'] ?? 24);
    
    if (!$postId || !$question || count($options) < 2 || count($options) > 6) {
        error('Cần ít nhất 2 và tối đa 6 lựa chọn');
    }
    
    // Check post ownership
    $post = $d->fetchOne("SELECT user_id FROM posts WHERE id = ? AND `status` = 'active'", [$postId]);
    if (!$post || intval($post['user_id']) !== $userId) { error('Không có quyền'); }
    
    // Check no existing poll
    $existing = $d->fetchOne("SELECT id FROM post_polls WHERE post_id = ?", [$postId]);
    if ($existing) { error('Bài viết đã có khảo sát'); }
    
    $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn * 3600);
    $d->query("INSERT INTO post_polls (post_id, question, expires_at) VALUES (?, ?, ?)", [$postId, $question, $expiresAt]);
    $pollId = $d->getLastInsertId();
    if (!$pollId) $pollId = intval($d->fetchOne("SELECT MAX(id) as m FROM post_polls")['m']);
    
    foreach ($options as $opt) {
        $text = trim($opt);
        if ($text) $d->query("INSERT INTO poll_options (poll_id, text) VALUES (?, ?)", [$pollId, $text]);
    }
    
    api_cache_flush('poll_');
    success('Đã tạo khảo sát', ['poll_id' => $pollId]);
}

// Vote
if ($method === 'POST' && $action === 'vote') {
    $userId = getAuthUserId();
    if (!$userId) { error('Auth required', 401); }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $pollId = intval($input['poll_id'] ?? 0);
    $optionId = intval($input['option_id'] ?? 0);
    
    if (!$pollId || !$optionId) { error('Missing params'); }
    
    // Check poll not expired
    $poll = $d->fetchOne("SELECT * FROM post_polls WHERE id = ?", [$pollId]);
    if (!$poll) { error('Poll not found'); }
    if ($poll['expires_at'] && strtotime($poll['expires_at']) < time()) { error('Khảo sát đã kết thúc'); }
    
    // Check not already voted
    $existing = $d->fetchOne("SELECT id, option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?", [$pollId, $userId]);
    if ($existing) {
        // Change vote
        $d->query("UPDATE poll_options SET vote_count = GREATEST(vote_count - 1, 0) WHERE id = ?", [$existing['option_id']]);
        $d->query("UPDATE poll_votes SET option_id = ? WHERE id = ?", [$optionId, $existing['id']]);
    } else {
        $d->query("INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)", [$pollId, $optionId, $userId]);
    }
    $d->query("UPDATE poll_options SET vote_count = vote_count + 1 WHERE id = ?", [$optionId]);
    
    api_cache_flush('poll_' . $poll['post_id']);
    success('Đã bỏ phiếu');
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
