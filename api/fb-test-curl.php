<?php
echo "PHP OK\n";
echo "cURL: " . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
$ch = curl_init("https://graph.facebook.com/v19.0/me?access_token=test123");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$r = curl_exec($ch);
$e = curl_error($ch);
$c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $c\nErr: $e\nResp: $r\n";
