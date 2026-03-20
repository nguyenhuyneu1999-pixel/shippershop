<?php
require_once __DIR__.'/../includes/config.php';
header('Content-Type: application/json');
echo json_encode(['t2' => generateJWT(2,'a@s.vn','admin'), 't3' => generateJWT(3,'b@s.vn','user')]);
