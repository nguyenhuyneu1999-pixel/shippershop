<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== follows table ===\n";
try {
    $cols=$d->fetchAll("SHOW COLUMNS FROM follows");
    foreach($cols as $c) echo $c['Field']." (".$c['Type'].")\n";
} catch(Throwable $e) {
    echo "ERROR: ".$e->getMessage()."\n";
}

echo "\n=== page_views table ===\n";
try {
    $c=$d->fetchOne("SELECT COUNT(*) as c FROM page_views")['c'];
    echo "EXISTS: $c rows\n";
} catch(Throwable $e) {
    echo "NOT FOUND\n";
}

echo "\n=== Test users query directly ===\n";
$realUserCond = "(u.email LIKE '%@shippershop.local' OR u.email = 'nguyenhuyneu1999@gmail.com' OR u.email = 'nguyenvanhuy12123@gmail.com')";
try {
    $users = $d->fetchAll("SELECT u.id, u.username, u.fullname, u.email, u.avatar, u.`status`, u.role, u.shipping_company, u.created_at,
        (SELECT COUNT(*) FROM posts WHERE user_id=u.id) as post_count,
        (SELECT COUNT(*) FROM follows WHERE following_id=u.id) as follower_count,
        CASE WHEN $realUserCond THEN 0 ELSE 1 END as is_seed
        FROM users u WHERE u.id > 1 ORDER BY u.id DESC LIMIT 2");
    echo "OK: ".count($users)." rows\n";
    foreach($users as $u) echo "  id={$u['id']} name={$u['fullname']} posts={$u['post_count']} followers={$u['follower_count']} seed={$u['is_seed']}\n";
} catch(Throwable $e) {
    echo "SQL ERROR: ".$e->getMessage()."\n";
}
