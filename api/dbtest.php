<?php
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');
$d=db();

echo "=== USER CREATION ANALYSIS ===\n\n";

// Check creation patterns - batch vs organic
echo "--- Users by creation time (grouped by minute) ---\n";
$batches=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m-%d %H:%i') as minute, COUNT(*) as cnt, MIN(id) as min_id, MAX(id) as max_id 
    FROM users WHERE id>1 
    GROUP BY DATE_FORMAT(created_at,'%Y-%m-%d %H:%i') 
    HAVING cnt >= 5
    ORDER BY minute");
echo "Batch creations (5+ users in same minute):\n";
foreach($batches as $b){
    echo "  ".$b['minute']." → ".$b['cnt']." users (id ".$b['min_id']."-".$b['max_id'].")\n";
}

echo "\n--- Users created individually (likely organic) ---\n";
$singles=$d->fetchAll("SELECT DATE_FORMAT(created_at,'%Y-%m-%d %H:%i') as minute, COUNT(*) as cnt
    FROM users WHERE id>1
    GROUP BY DATE_FORMAT(created_at,'%Y-%m-%d %H:%i')
    HAVING cnt < 5
    ORDER BY minute");
echo "Individual creations: ".count($singles)." time slots\n";
$organicIds=$d->fetchAll("SELECT u.id, u.username, u.fullname, u.email, u.created_at
    FROM users u
    WHERE u.id > 1
    AND (SELECT COUNT(*) FROM users u2 
         WHERE DATE_FORMAT(u2.created_at,'%Y-%m-%d %H:%i') = DATE_FORMAT(u.created_at,'%Y-%m-%d %H:%i')
         AND u2.id > 1) < 5
    ORDER BY u.created_at DESC LIMIT 20");
echo "\nPotential organic users (created alone, not in batch):\n";
foreach($organicIds as $u){
    echo "  id=".$u['id']." | ".$u['fullname']." | @".$u['username']." | ".$u['email']." | ".$u['created_at']."\n";
}

echo "\n--- User ID ranges and creation dates ---\n";
$ranges=$d->fetchAll("SELECT 
    CASE 
        WHEN id BETWEEN 3 AND 102 THEN 'Seed batch 1 (id 3-102)'
        WHEN id BETWEEN 103 AND 202 THEN 'Batch 103-202'
        WHEN id BETWEEN 203 AND 302 THEN 'Batch 203-302'
        WHEN id BETWEEN 303 AND 402 THEN 'Batch 303-402'
        WHEN id BETWEEN 403 AND 502 THEN 'Batch 403-502'
        WHEN id BETWEEN 503 AND 602 THEN 'Batch 503-602'
        WHEN id BETWEEN 603 AND 702 THEN 'Batch 603-702'
        WHEN id BETWEEN 703 AND 802 THEN 'Batch 703+'
        ELSE 'Other'
    END as batch,
    COUNT(*) as cnt,
    MIN(created_at) as first_created,
    MAX(created_at) as last_created
FROM users WHERE id > 1
GROUP BY batch
ORDER BY MIN(id)");
foreach($ranges as $r){
    echo "  ".$r['batch'].": ".$r['cnt']." users (".$r['first_created']." → ".$r['last_created'].")\n";
}

echo "\n--- Email patterns ---\n";
$emailPatterns=$d->fetchAll("SELECT 
    CASE 
        WHEN email LIKE '%@shippershop.local' THEN '@shippershop.local'
        WHEN email LIKE '%@gmail.com' THEN '@gmail.com'
        WHEN email LIKE '%@yahoo%' THEN '@yahoo'
        ELSE 'other'
    END as domain,
    COUNT(*) as cnt
FROM users WHERE id > 1
GROUP BY domain ORDER BY cnt DESC");
foreach($emailPatterns as $e){
    echo "  ".$e['domain'].": ".$e['cnt']." users\n";
}

echo "\n--- Admin user ---\n";
$admin=$d->fetchOne("SELECT id,username,fullname,email,created_at FROM users WHERE id=2");
echo "  ".$admin['fullname']." | ".$admin['email']." | ".$admin['created_at']."\n";

echo "\nDONE\n";
