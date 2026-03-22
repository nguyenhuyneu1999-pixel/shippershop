<?php
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');
$pdo = db()->getConnection();
$results = [];

// Add FULLTEXT indexes for search
$indexes = [
    "ALTER TABLE posts ADD FULLTEXT INDEX IF NOT EXISTS ft_content (content)",
    "ALTER TABLE posts ADD FULLTEXT INDEX IF NOT EXISTS ft_title (title)",
    "ALTER TABLE group_posts ADD FULLTEXT INDEX IF NOT EXISTS ft_gp_content (content)",
    "ALTER TABLE marketplace_listings ADD FULLTEXT INDEX IF NOT EXISTS ft_ml_search (title, description)",
    "ALTER TABLE users ADD FULLTEXT INDEX IF NOT EXISTS ft_user_name (fullname, username)",
];

foreach ($indexes as $sql) {
    try {
        $pdo->exec($sql);
        preg_match('/ft_\w+/', $sql, $m);
        $results[] = ($m[0] ?? '?') . ': OK';
    } catch (Exception $e) {
        preg_match('/ft_\w+/', $sql, $m);
        $results[] = ($m[0] ?? '?') . ': ' . substr($e->getMessage(), 0, 60);
    }
}

echo json_encode(['success' => true, 'results' => $results]);
