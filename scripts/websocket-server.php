<?php
/**
 * ShipperShop WebSocket Server — Real-time chat
 * VPS: php scripts/websocket-server.php (runs as daemon)
 * Shared hosting: không chạy (client dùng polling)
 * 
 * Port: 8080
 * Protocol: ws://shippershop.vn:8080
 * 
 * Events:
 *   client→server: {type:"auth", token:"JWT"} — authenticate
 *   client→server: {type:"message", conversation_id:1, content:"hi"}
 *   client→server: {type:"typing", conversation_id:1}
 *   client→server: {type:"read", conversation_id:1}
 *   server→client: {type:"new_message", data:{...}}
 *   server→client: {type:"typing", user_id:5, conversation_id:1}
 *   server→client: {type:"online", user_id:5, status:true}
 */

if (php_sapi_name() !== 'cli') die('CLI only');

// Check if Ratchet is available
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    echo "WebSocket server cần Ratchet. Cài đặt:\n";
    echo "  cd " . dirname(__DIR__) . " && composer require cboden/ratchet\n";
    echo "\nĐang chạy ở chế độ FALLBACK (Redis pub/sub polling)...\n\n";
    
    // Fallback: Redis pub/sub listener
    if (!class_exists('Redis')) {
        echo "Redis không có. WebSocket server không thể chạy.\n";
        exit(1);
    }
    
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->select(4);
    
    echo "[WS Fallback] Listening on Redis pub/sub...\n";
    
    // Simple polling-based relay
    while (true) {
        // Check for new events
        $channels = $redis->keys('ss:events:*');
        foreach ($channels ?: [] as $ch) {
            $events = $redis->lRange($ch, 0, 0);
            foreach ($events ?: [] as $e) {
                $data = json_decode($e, true);
                if ($data && (time() - ($data['ts'] ?? 0)) < 2) {
                    echo "[Event] " . ($data['type'] ?? '?') . " on $ch\n";
                }
            }
        }
        usleep(500000); // 0.5s
    }
    exit(0);
}

require $autoload;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ShipperShopChat implements MessageComponentInterface {
    protected $clients;
    protected $userConnections; // userId → [connections]
    protected $connectionUsers; // connectionId → userId
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->userConnections = [];
        $this->connectionUsers = [];
        echo "[WS] Server started on port 8080\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "[WS] New connection: {$conn->resourceId}\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) return;
        
        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'message':
                $this->handleMessage($from, $data);
                break;
            case 'typing':
                $this->handleTyping($from, $data);
                break;
            case 'read':
                $this->handleRead($from, $data);
                break;
        }
    }
    
    private function handleAuth($conn, $data) {
        $token = $data['token'] ?? '';
        // Verify JWT
        require_once __DIR__ . '/../includes/config.php';
        $parts = explode('.', $token);
        if (count($parts) !== 3) return;
        $payload = json_decode(base64_decode($parts[1]), true);
        $userId = intval($payload['user_id'] ?? 0);
        if (!$userId) return;
        
        $this->connectionUsers[$conn->resourceId] = $userId;
        if (!isset($this->userConnections[$userId])) $this->userConnections[$userId] = [];
        $this->userConnections[$userId][] = $conn;
        
        $conn->send(json_encode(['type' => 'auth_ok', 'user_id' => $userId]));
        
        // Broadcast online status
        $this->broadcast(['type' => 'online', 'user_id' => $userId, 'status' => true]);
        echo "[WS] Auth: user $userId\n";
    }
    
    private function handleMessage($from, $data) {
        $userId = $this->connectionUsers[$from->resourceId] ?? 0;
        if (!$userId) return;
        
        $convId = intval($data['conversation_id'] ?? 0);
        $content = trim($data['content'] ?? '');
        if (!$convId || !$content) return;
        
        // Save to DB
        require_once __DIR__ . '/../includes/db.php';
        $d = db();
        $d->query("INSERT INTO messages (conversation_id, sender_id, content, type, is_read, created_at) VALUES (?, ?, ?, 'text', 0, NOW())", [$convId, $userId, $content]);
        $d->query("UPDATE conversations SET last_message=?, last_message_at=NOW() WHERE id=?", [mb_substr($content, 0, 100), $convId]);
        
        $msgId = $d->getLastInsertId();
        $user = $d->fetchOne("SELECT fullname, avatar FROM users WHERE id=?", [$userId]);
        
        // Get conversation members
        $conv = $d->fetchOne("SELECT user1_id, user2_id, type FROM conversations WHERE id=?", [$convId]);
        $targetIds = [];
        if ($conv && $conv['type'] !== 'group') {
            $targetIds[] = $conv['user1_id'] == $userId ? $conv['user2_id'] : $conv['user1_id'];
        }
        
        // Send to all connected members
        $msgData = [
            'type' => 'new_message',
            'data' => [
                'id' => $msgId,
                'conversation_id' => $convId,
                'sender_id' => $userId,
                'sender_name' => $user ? $user['fullname'] : '',
                'sender_avatar' => $user ? $user['avatar'] : '',
                'content' => $content,
                'created_at' => date('c'),
            ]
        ];
        
        foreach ($targetIds as $tid) {
            $this->sendToUser(intval($tid), $msgData);
        }
        
        // Confirm to sender
        $from->send(json_encode(['type' => 'message_sent', 'data' => $msgData['data']]));
    }
    
    private function handleTyping($from, $data) {
        $userId = $this->connectionUsers[$from->resourceId] ?? 0;
        $convId = intval($data['conversation_id'] ?? 0);
        if (!$userId || !$convId) return;
        
        // Get other user(s)
        require_once __DIR__ . '/../includes/db.php';
        $conv = db()->fetchOne("SELECT user1_id, user2_id FROM conversations WHERE id=?", [$convId]);
        if ($conv) {
            $otherId = $conv['user1_id'] == $userId ? $conv['user2_id'] : $conv['user1_id'];
            $this->sendToUser(intval($otherId), ['type' => 'typing', 'user_id' => $userId, 'conversation_id' => $convId]);
        }
    }
    
    private function handleRead($from, $data) {
        $userId = $this->connectionUsers[$from->resourceId] ?? 0;
        $convId = intval($data['conversation_id'] ?? 0);
        if (!$userId || !$convId) return;
        
        require_once __DIR__ . '/../includes/db.php';
        db()->query("UPDATE messages SET is_read=1, read_at=NOW() WHERE conversation_id=? AND sender_id!=? AND is_read=0", [$convId, $userId]);
    }
    
    private function sendToUser($userId, $data) {
        foreach ($this->userConnections[$userId] ?? [] as $conn) {
            $conn->send(json_encode($data));
        }
    }
    
    private function broadcast($data) {
        $msg = json_encode($data);
        foreach ($this->clients as $client) {
            $client->send($msg);
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $userId = $this->connectionUsers[$conn->resourceId] ?? 0;
        if ($userId) {
            // Remove connection
            $this->userConnections[$userId] = array_filter(
                $this->userConnections[$userId] ?? [],
                function($c) use ($conn) { return $c !== $conn; }
            );
            if (empty($this->userConnections[$userId])) {
                unset($this->userConnections[$userId]);
                $this->broadcast(['type' => 'online', 'user_id' => $userId, 'status' => false]);
            }
            unset($this->connectionUsers[$conn->resourceId]);
        }
        $this->clients->detach($conn);
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "[WS] Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

$port = intval($argv[1] ?? 8080);
$server = IoServer::factory(
    new HttpServer(new WsServer(new ShipperShopChat())),
    $port
);
echo "[WS] Listening on 0.0.0.0:$port\n";
$server->run();
