<?php
// payOS Configuration - ShipperShop
// Đăng ký tại https://my.payos.vn để lấy keys
// Hỗ trợ cá nhân: MB, OCB, KienlongBank, ACB, BIDV

define('PAYOS_CLIENT_ID', '');       // Client ID từ payOS
define('PAYOS_API_KEY', '');         // API Key từ payOS  
define('PAYOS_CHECKSUM_KEY', '');    // Checksum Key từ payOS

// URLs
define('PAYOS_API_URL', 'https://api-merchant.payos.vn');
define('PAYOS_RETURN_URL', 'https://shippershop.vn/wallet.html?payment=success');
define('PAYOS_CANCEL_URL', 'https://shippershop.vn/wallet.html?payment=cancel');
define('PAYOS_WEBHOOK_URL', 'https://shippershop.vn/api/payos-webhook.php');
