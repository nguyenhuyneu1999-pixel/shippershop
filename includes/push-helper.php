<?php
// Push Notification Helper for ShipperShop
// Uses VAPID + Web Push API (no composer, no cURL needed)

if (!defined('APP_ACCESS')) define('APP_ACCESS', true);
require_once __DIR__ . '/vapid_keys.php';

function sendPushNotification($subscription, $payload = null) {
    $endpoint = $subscription['endpoint'];
    $parsed = parse_url($endpoint);
    $audience = $parsed['scheme'] . '://' . $parsed['host'];
    
    // Create VAPID JWT
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $claims = base64url_encode(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => VAPID_SUBJECT
    ]));
    $sigInput = $header . '.' . $claims;
    
    // Sign with ES256
    $privKey = openssl_pkey_get_private(VAPID_PRIVATE_PEM);
    if (!$privKey) return ['success' => false, 'error' => 'Invalid private key'];
    
    openssl_sign($sigInput, $derSig, $privKey, OPENSSL_ALGO_SHA256);
    $rawSig = derToRaw($derSig);
    $signature = base64url_encode($rawSig);
    $jwt = $sigInput . '.' . $signature;
    
    // Build headers
    $headers = [
        'Authorization: vapid t=' . $jwt . ',k=' . VAPID_PUBLIC_KEY,
        'TTL: 86400',
    ];
    
    $body = '';
    if ($payload !== null) {
        // Encrypt payload
        $encrypted = encryptPayload(
            $payload,
            $subscription['p256dh'],
            $subscription['auth']
        );
        if (!$encrypted) return ['success' => false, 'error' => 'Encryption failed'];
        
        $body = $encrypted['ciphertext'];
        $headers[] = 'Content-Type: application/octet-stream';
        $headers[] = 'Content-Encoding: aes128gcm';
        $headers[] = 'Content-Length: ' . strlen($body);
    } else {
        $headers[] = 'Content-Length: 0';
    }
    
    // Send via file_get_contents (no cURL needed)
    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headers),
            'content' => $body,
            'ignore_errors' => true,
            'timeout' => 10,
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    
    $response = @file_get_contents($endpoint, false, $ctx);
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/(\d{3})/', $statusLine, $m);
    $status = intval($m[1] ?? 0);
    
    return [
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'response' => $response
    ];
}

function encryptPayload($payload, $userPublicKey, $userAuth) {
    $userPub = base64url_decode($userPublicKey);
    $userAuthKey = base64url_decode($userAuth);
    
    // Generate local EC key pair
    $localKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    if (!$localKey) return null;
    $localDet = openssl_pkey_get_details($localKey);
    $localPub = chr(4) . $localDet['ec']['x'] . $localDet['ec']['y'];
    
    // ECDH shared secret
    $sharedSecret = computeECDH($localKey, $userPub);
    if (!$sharedSecret) return null;
    
    // Salt (16 random bytes)
    $salt = random_bytes(16);
    
    // IKM = HKDF(auth, shared_secret, "WebPush: info\0" + client_pub + server_pub, 32)
    $info = "WebPush: info\0" . $userPub . $localPub;
    $ikm = hkdf($userAuthKey, $sharedSecret, $info, 32);
    
    // Key = HKDF(salt, ikm, "Content-Encoding: aes128gcm\0", 16)
    $contentKey = hkdf($salt, $ikm, "Content-Encoding: aes128gcm\0", 16);
    
    // Nonce = HKDF(salt, ikm, "Content-Encoding: nonce\0", 12)
    $nonce = hkdf($salt, $ikm, "Content-Encoding: nonce\0", 12);
    
    // Pad payload
    $padded = "\x00\x00" . $payload;
    
    // AES-128-GCM encrypt
    $tag = '';
    $encrypted = openssl_encrypt($padded, 'aes-128-gcm', $contentKey, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($encrypted === false) return null;
    
    // Build aes128gcm record: salt(16) + rs(4) + idlen(1) + keyid(65) + ciphertext + tag
    $rs = pack('N', 4096);
    $header = $salt . $rs . chr(65) . $localPub;
    
    return ['ciphertext' => $header . $encrypted . $tag];
}

function computeECDH($localPrivKey, $remotePubKeyBin) {
    // Clear any stale OpenSSL errors
    while(openssl_error_string()){}
    
    // ASN.1 DER encoding for EC public key (P-256)
    $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $remotePubKeyBin;
    $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    
    $remotePubKey = openssl_pkey_get_public($pem);
    if (!$remotePubKey) return null;
    
    $shared = openssl_pkey_derive($remotePubKey, $localPrivKey, 32);
    return $shared ?: null;
}

function hkdf($salt, $ikm, $info, $length) {
    $prk = hash_hmac('sha256', $ikm, $salt, true);
    $t = '';
    $output = '';
    for ($i = 1; strlen($output) < $length; $i++) {
        $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
        $output .= $t;
    }
    return substr($output, 0, $length);
}

function createPemFromPrivateKey($b64Key) {
    $d = base64url_decode($b64Key);
    // Build EC private key DER
    $der = "\x30\x41\x02\x01\x01\x04\x20" . $d . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
    return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
}

function derToRaw($der) {
    $pos = 0;
    if (ord($der[$pos]) !== 0x30) return $der;
    $pos += 2;
    
    // R
    if (ord($der[$pos]) !== 0x02) return $der;
    $pos++;
    $rLen = ord($der[$pos]); $pos++;
    $r = substr($der, $pos, $rLen); $pos += $rLen;
    
    // S
    if (ord($der[$pos]) !== 0x02) return $der;
    $pos++;
    $sLen = ord($der[$pos]); $pos++;
    $s = substr($der, $pos, $sLen);
    
    $r = ltrim($r, "\x00");
    $s = ltrim($s, "\x00");
    $r = str_pad($r, 32, "\x00", STR_PAD_LEFT);
    $s = str_pad($s, 32, "\x00", STR_PAD_LEFT);
    
    return $r . $s;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
}

// High-level: Send notification to a user
function notifyUser($userId, $title, $body, $category = 'general', $url = '/') {
    $d = db();
    $subs = $d->fetchAll("SELECT * FROM push_subscriptions WHERE user_id = ?", [$userId]);
    $sent = 0;
    $payload = json_encode([
        'title' => $title,
        'body' => $body,
        'category' => $category,
        'url' => $url,
        'icon' => '/icons/icon-192.png',
        'badge' => '/icons/icon-72.png',
        'timestamp' => time()
    ]);
    
    foreach ($subs as $sub) {
        $result = sendPushNotification($sub, $payload);
        if ($result['success']) {
            $sent++;
        } elseif ($result['status'] === 410 || $result['status'] === 404) {
            // Subscription expired, remove it
            $d->query("DELETE FROM push_subscriptions WHERE id = ?", [$sub['id']]);
        }
    }
    return $sent;
}
