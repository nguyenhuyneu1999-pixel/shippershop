<?php
/**
 * XP Helper - Call from any API to award XP
 * Usage: awardXP($userId, 'post', 10, 'Đăng bài #123');
 */
function awardXP($userId, $action, $xp, $detail = '') {
    $d = db();
    
    // Rate limit: check daily cap
    $caps = ['post' => 5, 'comment' => 20, 'checkin' => 1, 'like_received' => 50];
    $cap = $caps[$action] ?? 100;
    $todayCount = $d->fetchOne("SELECT COUNT(*) as c FROM user_xp WHERE user_id = ? AND action = ? AND DATE(created_at) = CURDATE()", [$userId, $action])['c'];
    if ($todayCount >= $cap) return false;
    
    $d->query("INSERT INTO user_xp (user_id, action, xp, detail) VALUES (?, ?, ?, ?)", [$userId, $action, $xp, $detail]);
    
    // Update totals
    $total = $d->fetchOne("SELECT COALESCE(SUM(xp),0) as t FROM user_xp WHERE user_id = ?", [$userId])['t'];
    $level = 1;
    if ($total >= 15000) $level = 5;
    elseif ($total >= 5000) $level = 4;
    elseif ($total >= 2000) $level = 3;
    elseif ($total >= 500) $level = 2;
    
    $d->query("INSERT INTO user_streaks (user_id, total_xp, level) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE total_xp = ?, level = ?",
        [$userId, $total, $level, $total, $level]);
    
    return true;
}
