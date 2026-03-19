<?php
header('Content-Type: text/plain');
// Simple connectivity test
$ch = curl_init("https://graph.facebook.com/v19.0/?access_token=test");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$resp = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
curl_close($ch);
echo "IP: $ip\nHTTP: $code\nErr: $err\nResp: " . substr($resp,0,200);
