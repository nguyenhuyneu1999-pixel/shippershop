<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/config.php';
header('Content-Type: text/plain');

// Create test user with known password
$d=db();
$testEmail='test_flow@shippershop.local';
$testPass='test123456';
$ex=$d->fetchOne("SELECT id FROM users WHERE email=?",[$testEmail]);
if(!$ex){
    $hash=password_hash($testPass, PASSWORD_BCRYPT);
    $d->query("INSERT INTO users (email,username,password,fullname,avatar) VALUES (?,?,?,?,?)",
        [$testEmail,'test_flow',$hash,'Test Flow User',null]);
    $uid=$d->getLastInsertId();
    if(!$uid) $uid=$d->fetchOne("SELECT MAX(id) as m FROM users")['m'];
    echo "Created test user id=$uid\n";
} else {
    $uid=$ex['id'];
    // Update password
    $hash=password_hash($testPass, PASSWORD_BCRYPT);
    $d->query("UPDATE users SET password=? WHERE id=?",[$hash,$uid]);
    echo "Updated test user id=$uid\n";
}

// Generate JWT
$header=base64_encode(json_encode(['typ'=>'JWT','alg'=>'HS256']));
$payload=base64_encode(json_encode(['user_id'=>intval($uid),'exp'=>time()+86400]));
$header=rtrim(strtr($header,'+/','-_'),'=');
$payload=rtrim(strtr($payload,'+/','-_'),'=');
$sig=hash_hmac('sha256',"$header.$payload",JWT_SECRET,true);
$sig=rtrim(strtr(base64_encode($sig),'+/','-_'),'=');
$token="$header.$payload.$sig";
echo "JWT: $token\n";
echo "User ID: $uid\n";

// Verify it works
echo "\nVerify token:\n";
$parts=explode('.',$token);
$check=hash_hmac('sha256',$parts[0].'.'.$parts[1],JWT_SECRET,true);
$checkSig=rtrim(strtr(base64_encode($check),'+/','-_'),'=');
echo "Sig match: ".($checkSig===$parts[2]?'YES':'NO')."\n";
