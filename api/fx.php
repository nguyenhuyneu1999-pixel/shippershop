<?php
error_reporting(E_ALL); ini_set("display_errors",1);
header("Content-Type: text/plain");
require_once __DIR__ . '/../includes/db.php';
$d = db();

// FIX: Activate all subscription plans
echo "=== Activate plans ===\n";
$d->query("UPDATE subscription_plans SET is_active = 1 WHERE is_active = 0 OR is_active IS NULL");
$plans = $d->fetchAll("SELECT id, name, is_active FROM subscription_plans ORDER BY id");
foreach ($plans as $p) echo "  #{$p['id']} {$p['name']} active={$p['is_active']}\n";

// FIX: Update sort_order
$d->query("UPDATE subscription_plans SET sort_order = id WHERE sort_order IS NULL OR sort_order = 0");

// FIX: Post types still empty
echo "\n=== Fix empty post types ===\n";
$empty = $d->fetchAll("SELECT id, content FROM posts WHERE (type = '' OR type IS NULL) AND `status` = 'active' AND LENGTH(content) > 50 LIMIT 30");
echo count($empty) . " posts with empty type\n";
foreach ($empty as $p) {
    $c = mb_strtolower($p['content']);
    $type = 'discussion';
    if (mb_strpos($c,'mẹo')!==false || mb_strpos($c,'tips')!==false || mb_strpos($c,'checklist')!==false || mb_strpos($c,'lưu ý')!==false) $type='tips';
    elseif (mb_strpos($c,'hỏi')!==false || mb_substr_count($c,'?')>=2) $type='question';
    elseif (mb_strpos($c,'cảnh báo')!==false || mb_strpos($c,'cẩn thận')!==false) $type='warning';
    elseif (mb_strpos($c,'review')!==false || mb_strpos($c,'so sánh')!==false) $type='review';
    elseif (mb_strpos($c,'😂')!==false || mb_strpos($c,'hài')!==false) $type='fun';
    $d->query("UPDATE posts SET type = ? WHERE id = ?", [$type, $p['id']]);
    echo "  #{$p['id']} → $type\n";
}

echo "\nDone!\n";
