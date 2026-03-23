<?php
// PHP + session_start
session_start();
header('Content-Type: application/json');
echo '{"ok":true,"session":true}';
