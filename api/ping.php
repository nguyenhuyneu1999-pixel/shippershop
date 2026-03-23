<?php
// Absolute minimal PHP — no requires, no session, no DB
header('Content-Type: application/json');
echo '{"ok":true,"t":' . (microtime(true) * 1000) . '}';
