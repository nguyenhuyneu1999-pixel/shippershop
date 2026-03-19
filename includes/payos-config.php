<?php
// payOS Configuration - ShipperShop
// Đăng ký tại https://my.payos.vn để lấy keys
// Hỗ trợ cá nhân: MB, OCB, KienlongBank, ACB, BIDV

define('PAYOS_CLIENT_ID', '488de223-75a7-4174-994d-0c85705462e4');
define('PAYOS_API_KEY', '5057380e-e4a9-47e0-8fdc-d356ccfe1be0');
define('PAYOS_CHECKSUM_KEY', '99378cbb57df362a13a58fcd0d486e7a12c5267bde26c1f03bbaccd2ad00addc');

// URLs
define('PAYOS_API_URL', 'https://api-merchant.payos.vn');
define('PAYOS_RETURN_URL', 'https://shippershop.vn/wallet.html?payment=success');
define('PAYOS_CANCEL_URL', 'https://shippershop.vn/wallet.html?payment=cancel');
define('PAYOS_WEBHOOK_URL', 'https://shippershop.vn/api/payos-webhook.php');
