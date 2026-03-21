<?php
// ShipperShop API v2 — OG Meta Tags for social sharing
// Returns HTML meta tags for a post/user/group for social media crawlers
// Usage: ?type=post&id=X or ?type=user&id=X
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();
$type=$_GET['type']??'post';
$id=intval($_GET['id']??0);

if(!$id){echo json_encode(['success'=>false]);exit;}

$meta=['title'=>'ShipperShop','description'=>'Cộng đồng shipper Việt Nam','image'=>'https://shippershop.vn/icons/icon-512.png','url'=>'https://shippershop.vn','type'=>'website'];

try {

if($type==='post'){
    $post=cache_remember('og_post_'.$id, function() use($d,$id) {
        return $d->fetchOne("SELECT p.content,p.images,p.video_url,u.fullname as user_name,u.avatar FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$id]);
    }, 300);
    if($post){
        $meta['title']=mb_substr(($post['user_name']??'Shipper').' — '.($post['content']??''),0,70).' | ShipperShop';
        $meta['description']=mb_substr($post['content']??'Bài viết trên ShipperShop',0,160);
        $meta['url']='https://shippershop.vn/post-detail.html?id='.$id;
        $meta['type']='article';
        if($post['images']){$imgs=json_decode($post['images'],true);if(is_array($imgs)&&$imgs)$meta['image']='https://shippershop.vn'.$imgs[0];}
        elseif($post['user_avatar']??'') $meta['image']='https://shippershop.vn'.$post['avatar'];
    }
}

if($type==='user'){
    $user=cache_remember('og_user_'.$id, function() use($d,$id) {
        return $d->fetchOne("SELECT fullname,bio,avatar,shipping_company FROM users WHERE id=? AND `status`='active'",[$id]);
    }, 300);
    if($user){
        $meta['title']=($user['fullname']??'User').' | ShipperShop';
        $meta['description']=($user['bio']??'Shipper tại '.($user['shipping_company']??'ShipperShop'));
        $meta['url']='https://shippershop.vn/user.html?id='.$id;
        $meta['type']='profile';
        if($user['avatar']) $meta['image']='https://shippershop.vn'.$user['avatar'];
    }
}

if($type==='group'){
    $group=cache_remember('og_group_'.$id, function() use($d,$id) {
        return $d->fetchOne("SELECT name,description,avatar,member_count FROM `groups` WHERE id=?",[$id]);
    }, 300);
    if($group){
        $meta['title']=($group['name']??'Nhóm').' | ShipperShop';
        $meta['description']=$group['description']??($group['member_count'].' thành viên');
        $meta['url']='https://shippershop.vn/group.html?id='.$id;
        if($group['avatar']) $meta['image']='https://shippershop.vn'.$group['avatar'];
    }
}

} catch (\Throwable $e) {}

echo json_encode(['success'=>true,'data'=>$meta],JSON_UNESCAPED_UNICODE);
