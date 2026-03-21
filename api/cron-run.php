<?php
/**
 * Cron trigger via HTTP — requires secret key
 * Usage: curl https://shippershop.vn/api/cron-run.php?key=ss_cron_8f3a2b1c
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
$_GET['key'] = $_GET['key'] ?? '';
require_once __DIR__ . '/../includes/cron/runner.php';
