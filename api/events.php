<?php
/**
 * ShipperShop Events API
 * GET ?action=list&group_id=X — list events
 * GET ?action=upcoming — all upcoming events
 * POST ?action=create — create event (group admin/mod)
 * POST ?action=rsvp — RSVP to event
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
$action = $_GET['action'] ?? 'upcoming';

if ($action === 'upcoming') {
    api_try_cache('events_upcoming', 120);
    $events = $d->fetchAll(
        "SELECT e.*, g.name as group_name, u.fullname as creator_name, u.avatar as creator_avatar,
                (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND `status` = 'going') as going_count,
                (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND `status` = 'interested') as interested_count
         FROM events e
         JOIN `groups` g ON e.group_id = g.id
         JOIN users u ON e.created_by = u.id
         WHERE e.event_date >= NOW() AND e.`status` = 'active'
         ORDER BY e.event_date ASC LIMIT 20"
    );
    success('OK', $events ?: []);
}

if ($action === 'list') {
    $gid = intval($_GET['group_id'] ?? 0);
    if (!$gid) { error('Missing group_id'); }
    api_try_cache('events_group_' . $gid, 120);
    
    $events = $d->fetchAll(
        "SELECT e.*, u.fullname as creator_name, u.avatar as creator_avatar,
                (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND `status` = 'going') as going_count,
                (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND `status` = 'interested') as interested_count
         FROM events e JOIN users u ON e.created_by = u.id
         WHERE e.group_id = ? AND e.`status` = 'active'
         ORDER BY e.event_date ASC LIMIT 20",
        [$gid]
    );
    success('OK', $events ?: []);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    $uid = getAuthUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    $gid = intval($input['group_id'] ?? 0);
    $title = trim($input['title'] ?? '');
    $desc = trim($input['description'] ?? '');
    $location = trim($input['location'] ?? '');
    $eventDate = $input['event_date'] ?? '';
    
    if (!$gid || !$title || !$eventDate) { error('Thiếu thông tin bắt buộc'); }
    if (strlen($title) < 5) { error('Tiêu đề tối thiểu 5 ký tự'); }
    
    // Check admin/mod
    $member = $d->fetchOne("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?", [$gid, $uid]);
    if (!$member || !in_array($member['role'], ['admin', 'moderator'])) { error('Chỉ admin/mod tạo sự kiện'); }
    
    $d->query("INSERT INTO events (group_id, created_by, title, description, location, event_date, `status`, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
        [$gid, $uid, $title, $desc, $location, $eventDate]);
    
    api_cache_flush('events_');
    success('Đã tạo sự kiện!');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'rsvp') {
    $uid = getAuthUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    $eventId = intval($input['event_id'] ?? 0);
    $rsvpStatus = $input['status'] ?? 'going'; // going, interested, not_going
    
    if (!$eventId) { error('Missing event_id'); }
    
    $existing = $d->fetchOne("SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ?", [$eventId, $uid]);
    if ($existing) {
        $d->query("UPDATE event_rsvps SET `status` = ? WHERE event_id = ? AND user_id = ?", [$rsvpStatus, $eventId, $uid]);
    } else {
        $d->query("INSERT INTO event_rsvps (event_id, user_id, `status`, created_at) VALUES (?, ?, ?, NOW())", [$eventId, $uid, $rsvpStatus]);
    }
    
    api_cache_flush('events_');
    success('Đã cập nhật RSVP');
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
