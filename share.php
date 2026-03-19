<?php
// Dynamic Open Graph meta tags for social media preview
// URL: /share.php?type=post&id=123
// Facebook/Zalo crawlers see OG tags → beautiful link preview → redirect user to actual page
require_once __DIR__ . '/includes/db.php';
$d = db();
$type = $_GET['type'] ?? 'post';
$id = intval($_GET['id'] ?? 0);
$base = 'https://shippershop.vn';

$title = 'ShipperShop - Cộng đồng Shipper Việt Nam';
$desc = 'Mạng xã hội dành riêng cho shipper Việt Nam. Chia sẻ kinh nghiệm, mẹo giao hàng, tìm đơn.';
$image = $base . '/icons/og-image.png';
$url = $base;

if ($type === 'post' && $id) {
    $p = $d->fetchOne("SELECT p.*, u.fullname FROM posts p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'", [$id]);
    if ($p) {
        $content = mb_substr(strip_tags($p['content']), 0, 150);
        $title = ($p['fullname'] ?? 'Shipper') . ' trên ShipperShop';
        $desc = $content ?: 'Xem bài viết trên ShipperShop';
        $url = $base . '/post-detail.html?id=' . $id;
        $imgs = json_decode($p['images'] ?? '[]', true);
        if ($imgs && count($imgs)) $image = $base . $imgs[0];
    }
} elseif ($type === 'group' && $id) {
    $g = $d->fetchOne("SELECT * FROM `groups` WHERE id=? AND `status`='active'", [$id]);
    if ($g) {
        $title = $g['name'] . ' - Cộng đồng ShipperShop';
        $desc = ($g['description'] ?? 'Tham gia cộng đồng shipper') . ' · ' . ($g['member_count'] ?? 0) . ' thành viên';
        $url = $base . '/group.html?slug=' . ($g['slug'] ?? $id);
        if (!empty($g['icon_image'])) $image = $base . $g['icon_image'];
    }
} elseif ($type === 'listing' && $id) {
    $l = $d->fetchOne("SELECT * FROM marketplace_listings WHERE id=? AND `status`='active'", [$id]);
    if ($l) {
        $price = $l['price'] > 0 ? number_format($l['price']) . 'đ' : 'Miễn phí';
        $title = $l['title'] . ' - ' . $price . ' | ShipperShop Chợ';
        $desc = mb_substr($l['description'] ?? 'Xem sản phẩm trên ShipperShop', 0, 150);
        $url = $base . '/listing.html?id=' . $id;
        $imgs = json_decode($l['images'] ?? '[]', true);
        if ($imgs && count($imgs)) $image = $base . $imgs[0];
    }
} elseif ($type === 'invite') {
    $ref = $_GET['ref'] ?? '';
    $title = 'Tham gia ShipperShop - Cộng đồng Shipper #1 Việt Nam';
    $desc = 'Bạn được mời tham gia ShipperShop! Chia sẻ kinh nghiệm giao hàng, mẹo tiết kiệm xăng, tìm đơn hàng.';
    $url = $base . '/register.html' . ($ref ? '?ref=' . $ref : '');
}

$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
$desc = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $title ?></title>
<meta name="description" content="<?= $desc ?>">
<meta property="og:type" content="article">
<meta property="og:url" content="<?= $url ?>">
<meta property="og:title" content="<?= $title ?>">
<meta property="og:description" content="<?= $desc ?>">
<meta property="og:image" content="<?= $image ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="ShipperShop">
<meta property="og:locale" content="vi_VN">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= $title ?>">
<meta name="twitter:description" content="<?= $desc ?>">
<meta name="twitter:image" content="<?= $image ?>">
<link rel="icon" href="/icons/icon-72.png">
<script>location.replace("<?= $url ?>");</script>
</head><body><p>Đang chuyển hướng... <a href="<?= $url ?>">Bấm đây</a></p></body></html>
