<?php
header('Content-Type: text/plain');
echo "=== Facebook API Test ===\n";
echo "PHP: " . phpversion() . "\n";
echo "cURL: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n\n";

// Test 1: basic HTTPS
$ch = curl_init("https://httpbin.org/get");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$r = curl_exec($ch);
$e = curl_error($ch);
$c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "httpbin.org: HTTP=$c err=$e len=" . strlen($r) . "\n";

// Test 2: Facebook
$ch = curl_init("https://graph.facebook.com/v19.0/me?access_token=test123");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$r = curl_exec($ch);
$e = curl_error($ch);
$c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
curl_close($ch);
echo "graph.facebook.com: HTTP=$c ip=$ip err=$e\n";
echo "Response: " . substr($r, 0, 200) . "\n";
