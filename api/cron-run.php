<?php
// Cron trigger via HTTP
$_GET['key'] = $_GET['key'] ?? '';
require_once __DIR__ . '/../includes/cron/runner.php';
