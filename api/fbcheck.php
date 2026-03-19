<?php
header('Cache-Control: no-store');
header('Content-Type: text/plain');
echo "v5\n";
$ch = curl_init("https://graph.facebook.com/v19.0/me?access_token=test");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$r = curl_exec($ch);
$e = curl_error($ch);
$c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP=$c\nErr=$e\nResp=" . substr($r,0,200) . "\n";
