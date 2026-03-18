<?php
define('APP_ACCESS', true);
require_once '/home/nhshiw2j/public_html/includes/config.php';
require_once '/home/nhshiw2j/public_html/includes/db.php';
header('Content-Type: text/plain; charset=utf-8');
$pdo = db()->getConnection();

echo "=== conversations columns ===\n";
$cols = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo "  {$c['Field']} ({$c['Type']}) {$c['Default']}\n";

echo "\n=== messages columns ===\n";
$cols2 = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols2 as $c) echo "  {$c['Field']} ({$c['Type']})\n";

echo "\n=== Sample conversations ===\n";
$cvs = $pdo->query("SELECT * FROM conversations LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cvs as $c) echo "  id={$c['id']} u1={$c['user1_id']} u2={$c['user2_id']} status={$c['status']} last={$c['last_message_at']}\n";

echo "\n=== Sample messages ===\n";
$msgs = $pdo->query("SELECT id, conversation_id, sender_id, type, LEFT(content,40) as preview, file_url, is_read FROM messages ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($msgs as $m) echo "  id={$m['id']} conv={$m['conversation_id']} sender={$m['sender_id']} type={$m['type']} read={$m['is_read']} \"{$m['preview']}\"\n";

echo "\n=== messages-api endpoints ===\n";
$api = file_get_contents('/home/nhshiw2j/public_html/api/messages-api.php');
preg_match_all('/action===?"([^"]+)"/', $api, $matches);
echo implode(', ', array_unique($matches[1])) . "\n";
