<?php
/**
 * Cron trigger via HTTP — requires secret key
 * Usage: curl https://shippershop.vn/api/cron-run.php?key=ss_cron_8f3a2b1c
 */
$_GET['key'] = $_GET['key'] ?? '';
require_once __DIR__ . '/../includes/cron/runner.php';
