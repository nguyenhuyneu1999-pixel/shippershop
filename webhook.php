<?php
$secret = 'shippershop2026';
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$payload = file_get_contents('php://input');
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $hash)) {
    http_response_code(403);
    die('Invalid signature');
}

$output = shell_exec('cd /home/nhshiw2j/public_html && git pull origin main 2>&1');
file_put_contents('deploy.log', date('Y-m-d H:i:s') . "\n" . $output . "\n\n", FILE_APPEND);
echo "Deployed successfully";
?>
