<?php
header('Content-Type: text/plain');
// Test outbound HTTPS
$tests = [
    'google' => 'https://www.google.com',
    'facebook' => 'https://graph.facebook.com/v19.0/?access_token=test',
    'httpbin' => 'https://httpbin.org/get',
];
foreach ($tests as $name => $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $r = curl_exec($ch);
    $e = curl_error($ch);
    $c = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "$name: HTTP=$c len=" . strlen($r) . " err=$e\n";
}
