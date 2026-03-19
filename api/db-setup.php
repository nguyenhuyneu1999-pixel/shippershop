<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
header('Content-Type: text/plain');

// Generate admin JWT for user id=2
$uid = 2;
$header = base64_encode(json_encode(['typ'=>'JWT','alg'=>'HS256']));
$payload = base64_encode(json_encode(['user_id'=>$uid,'exp'=>time()+86400]));
$header = rtrim(strtr($header,'+/','-_'),'=');
$payload = rtrim(strtr($payload,'+/','-_'),'=');
$sig = hash_hmac('sha256',"$header.$payload",JWT_SECRET,true);
$sig = rtrim(strtr(base64_encode($sig),'+/','-_'),'=');
echo "$header.$payload.$sig";
