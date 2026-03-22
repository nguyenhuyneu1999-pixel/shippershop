<?php
/**
 * ShipperShop Share Page — generates OG tags for social media previews
 * URL: /share.php?type=post&id=123
 */
define('APP_ACCESS', true);
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$type = $_GET['type'] ?? 'post';
$id = intval($_GET['id'] ?? 0);

$title = 'ShipperShop';
$desc = 'Cộng đồng shipper Việt Nam';
$image = 'https://shippershop.vn/icons/icon-512.png';
$url = 'https://shippershop.vn/';

if ($type === 'post' && $id) {
    $post = db()->fetchOne("SELECT p.content, p.images, u.fullname FROM posts p LEFT JOIN users u ON p.user_id = u.id WHERE p.id = ? AND p.`status` = 'active'", [$id]);
    if ($post) {
        $title = mb_substr($post['content'] ?? '', 0, 60) . '... — ShipperShop';
        $desc = ($post['fullname'] ?? 'Shipper') . ' chia sẻ trên ShipperShop';
        if ($post['images']) {
            $imgs = json_decode($post['images'], true);
            if ($imgs && !empty($imgs[0])) $image = 'https://shippershop.vn' . $imgs[0];
        }
        $url = "https://shippershop.vn/post-detail.html?id=$id";
    }
} elseif ($type === 'group' && $id) {
    $group = db()->fetchOne("SELECT name, description, icon_image FROM `groups` WHERE id = ?", [$id]);
    if ($group) {
        $title = $group['name'] . ' — ShipperShop';
        $desc = $group['description'] ?? '';
        if ($group['icon_image']) $image = 'https://shippershop.vn' . $group['icon_image'];
        $url = "https://shippershop.vn/group.html?id=$id";
    }
}

$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars(mb_substr($desc, 0, 160), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?=$title?></title>
<meta property="og:title" content="<?=$title?>">
<meta property="og:description" content="<?=$desc?>">
<meta property="og:image" content="<?=$image?>">
<meta property="og:url" content="<?=$url?>">
<meta property="og:type" content="article">
<meta name="twitter:card" content="summary_large_image">
<meta http-equiv="refresh" content="0;url=<?=$url?>">
</head>
<body>
<p>Đang chuyển hướng... <a href="<?=$url?>">Nhấn đây nếu không tự chuyển</a></p>
</body>
</html>
