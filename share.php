<?php
/**
 * Share proxy - Provides OG meta tags for social media preview
 * URL: /share.php?id=5
 * When Zalo/Facebook crawls this, they get title + image + description
 * When user clicks, they get redirected to community.html
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
$db = db();

$postId = intval($_GET['id'] ?? 0);
$title = 'ShipperShop - Cộng đồng';
$desc = 'Xem bài viết trên ShipperShop';
$image = 'https://shippershop.vn/images/logo.png';
$url = "https://shippershop.vn/community.html";

if ($postId > 0) {
    $post = $db->fetchOne(
        "SELECT p.*, u.fullname as user_name FROM posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.status = 'active'",
        [$postId]
    );
    if ($post) {
        $userName = $post['user_name'] ?? 'Người dùng';
        $title = $userName . ' trên ShipperShop';
        $content = strip_tags($post['content'] ?? '');
        $desc = mb_strlen($content) > 200 ? mb_substr($content, 0, 200) . '...' : $content;
        if (empty($desc)) $desc = 'Xem bài viết trên ShipperShop';
        $images = json_decode($post['images'] ?? '[]', true);
        if (!empty($images) && is_array($images) && !empty($images[0])) {
            $img = $images[0];
            if (strpos($img, 'http') !== 0) $img = 'https://shippershop.vn' . $img;
            $image = $img;
        }
        $url = "https://shippershop.vn/community.html?post=" . $postId;
    }
}

$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
$image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta property="og:title" content="<?= $title ?>">
<meta property="og:description" content="<?= $desc ?>">
<meta property="og:image" content="<?= $image ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:url" content="<?= $url ?>">
<meta property="og:type" content="article">
<meta property="og:site_name" content="ShipperShop">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $title ?>">
<meta name="twitter:description" content="<?= $desc ?>">
<meta name="twitter:image" content="<?= $image ?>">
<title><?= $title ?></title>
<script>window.location.href = '<?= $url ?>';</script>
</head>
<body><p>Đang chuyển hướng đến ShipperShop...</p><a href="<?= $url ?>">Bấm vào đây nếu không tự chuyển</a></body>
</html>