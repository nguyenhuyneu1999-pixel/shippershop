<?php
session_start();
define('APP_ACCESS', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/api-cache.php';
require_once __DIR__ . '/../includes/api-error-handler.php';
try { require_once __DIR__ . '/../includes/redis-rate-limiter.php'; apiRateLimit('groups.php', 120); } catch (Throwable $e) {}
setupApiErrorHandler();
require_once __DIR__ . '/auth-check.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function gOk($msg, $data = []) { $j=json_encode(['success'=>true,'message'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); if(isset($GLOBALS['_ssCacheKey'])&&function_exists('_ssCacheSave'))_ssCacheSave($j); echo $j; exit; }
function gErr($msg, $code = 400) { http_response_code($code); echo json_encode(['success'=>false,'message'=>$msg], JSON_UNESCAPED_UNICODE); exit; }

$d = db();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {

if ($method === 'GET') {
    // Cache group listings 45s
    $gAction = $_GET['action'] ?? '';
    if (in_array($gAction, ['discover', 'categories', ''])) {
        $_grpCacheKey = 'groups_' . md5($gAction . json_encode($_GET));
        api_try_cache($_grpCacheKey, 45);
    }

    if ($action === 'categories') {
        $cats = $d->fetchAll("SELECT * FROM group_categories WHERE parent_id IS NULL ORDER BY sort_order", []);
        $subs = $d->fetchAll("SELECT * FROM group_categories WHERE parent_id IS NOT NULL ORDER BY sort_order", []);
        $subMap = [];
        foreach ($subs as $s) { $subMap[$s['parent_id']][] = $s; }
        foreach ($cats as &$c) { $c['children'] = $subMap[$c['id']] ?? []; }
        gOk('OK', $cats);
    }

    if ($action === 'discover' || $action === '') {
        $uid = getOptionalAuthUserId();
        $myGroupIds = [];
        if ($uid) { $myGroupIds = array_column($d->fetchAll("SELECT group_id FROM group_members WHERE user_id = ?", [$uid]), 'group_id'); }

        $popular = $d->fetchAll("SELECT g.*, gc.name as cat_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE g.status = 'active' ORDER BY g.member_count DESC LIMIT 10", []);
        foreach ($popular as &$p) { $p['is_member'] = in_array($p['id'], $myGroupIds); }

        $recommended = [];
        if ($uid) {
            $notIn = empty($myGroupIds) ? '' : ' AND g.id NOT IN (' . implode(',', array_map('intval', $myGroupIds)) . ')';
            $recommended = $d->fetchAll("SELECT g.*, gc.name as cat_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE g.status = 'active' $notIn ORDER BY g.member_count DESC LIMIT 6", []);
            foreach ($recommended as &$r) { $r['is_member'] = false; }
        }

        $cats = $d->fetchAll("SELECT id, name, slug, icon FROM group_categories WHERE parent_id IS NULL ORDER BY sort_order LIMIT 6", []);
        $byCategory = [];
        foreach ($cats as $cat) {
            $groups = $d->fetchAll("SELECT g.*, gc.name as cat_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE g.status = 'active' AND (g.category_id = ? OR g.category_id IN (SELECT id FROM group_categories WHERE parent_id = ?)) ORDER BY g.member_count DESC LIMIT 4", [$cat['id'], $cat['id']]);
            foreach ($groups as &$g) { $g['is_member'] = in_array($g['id'], $myGroupIds); }
            if (!empty($groups)) { $byCategory[] = ['category' => $cat, 'groups' => $groups]; }
        }
        gOk('OK', ['popular' => $popular, 'recommended' => $recommended, 'by_category' => $byCategory]);
    }

    if ($action === 'category') {
        $slug = $_GET['slug'] ?? '';
        $cat = $d->fetchOne("SELECT * FROM group_categories WHERE slug = ?", [$slug]);
        if (!$cat) gErr('Khong tim thay', 404);
        $uid = getOptionalAuthUserId();
        $myGroupIds = [];
        if ($uid) { $myGroupIds = array_column($d->fetchAll("SELECT group_id FROM group_members WHERE user_id = ?", [$uid]), 'group_id'); }
        $subs = $d->fetchAll("SELECT * FROM group_categories WHERE parent_id = ? ORDER BY sort_order", [$cat['id']]);
        $catIds = [$cat['id']];
        foreach ($subs as $s) { $catIds[] = $s['id']; }
        $ph = implode(',', array_fill(0, count($catIds), '?'));
        $groups = $d->fetchAll("SELECT g.*, gc.name as cat_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE g.status = 'active' AND g.category_id IN ($ph) ORDER BY g.member_count DESC", $catIds);
        foreach ($groups as &$g) { $g['is_member'] = in_array($g['id'], $myGroupIds); }
        gOk('OK', ['category' => $cat, 'subcategories' => $subs, 'groups' => $groups]);
    }

    if ($action === 'detail') {
        $slug = $_GET['slug'] ?? '';
        $gid = intval($_GET['id'] ?? 0);
        $where = $slug ? "g.slug = ?" : "g.id = ?";
        $param = $slug ?: $gid;
        $group = $d->fetchOne("SELECT g.*, u.fullname as creator_name, u.avatar as creator_avatar, gc.name as cat_name FROM `groups` g JOIN users u ON g.creator_id = u.id LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE $where AND g.status = 'active'", [$param]);
        if (!$group) gErr('Khong tim thay', 404);
        $uid = getOptionalAuthUserId();
        $group['is_member'] = false;
        $group['member_role'] = null;
        if ($uid) {
            $m = $d->fetchOne("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?", [$group['id'], $uid]);
            if ($m) { $group['is_member'] = true; $group['member_role'] = $m['role']; }
        }
        $group['rules'] = $d->fetchAll("SELECT * FROM group_rules WHERE group_id = ? ORDER BY rule_order", [$group['id']]) ?: [];
        $group['moderators'] = $d->fetchAll("SELECT u.id, u.fullname, u.avatar, u.username, gm.role FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? AND gm.role IN ('admin','moderator') ORDER BY FIELD(gm.role,'admin','moderator')", [$group['id']]) ?: [];
        gOk('OK', $group);
    }

    if ($action === 'posts') {
        $gid = intval($_GET['group_id'] ?? 0);
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20; $offset = ($page - 1) * $limit;
        $sort = $_GET['sort'] ?? 'new';
        $orderBy = 'gp.created_at DESC';
        if ($sort === 'hot') $orderBy = '(gp.likes_count * 2 + gp.comments_count * 3) DESC, gp.created_at DESC';
        if ($sort === 'top') $orderBy = 'gp.likes_count DESC';
        $total = $d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE group_id = ? AND status = 'active'", [$gid]);
        $posts = $d->fetchAll("SELECT gp.*, u.fullname as user_name, u.avatar as user_avatar, u.username, u.shipping_company FROM group_posts gp JOIN users u ON gp.user_id = u.id WHERE gp.group_id = ? AND gp.status = 'active' ORDER BY $orderBy LIMIT $limit OFFSET $offset", [$gid]);
        $uid = getOptionalAuthUserId();
        if ($uid && !empty($posts)) {
            $postIds = array_column($posts, 'id');
            $ph = implode(',', array_fill(0, count($postIds), '?'));
            $liked = $d->fetchAll("SELECT post_id FROM group_post_likes WHERE user_id = ? AND post_id IN ($ph)", array_merge([$uid], $postIds));
            $likedSet = array_flip(array_column($liked, 'post_id'));
            foreach ($posts as &$p) { $p['user_liked'] = isset($likedSet[$p['id']]); }
        } else { foreach ($posts as &$p) { $p['user_liked'] = false; } }
        $totalCount = intval($total['c'] ?? 0);
        $totalPages = max(1, ceil($totalCount / $limit));
        gOk('OK', ['posts' => $posts, 'total' => $totalCount, 'total_pages' => $totalPages, 'page' => $page]);
    }

    if ($action === 'members') {
        $gid = intval($_GET['group_id'] ?? 0);
        $members = $d->fetchAll("SELECT u.id, u.fullname, u.avatar, u.username, u.shipping_company, gm.role, gm.joined_at FROM group_members gm JOIN users u ON gm.user_id = u.id WHERE gm.group_id = ? ORDER BY FIELD(gm.role,'admin','moderator','member'), gm.joined_at LIMIT 50", [$gid]);
        gOk('OK', $members ?: []);
    }

    if ($action === 'leaderboard') {
        $gid = intval($_GET['group_id'] ?? 0);
        $type = $_GET['type'] ?? 'posts';
        $month = $_GET['month'] ?? date('Y-m');
        $sd = $month . '-01 00:00:00';
        $ed = date('Y-m-t 23:59:59', strtotime($sd));
        if ($type === 'posts') {
            $leaders = $d->fetchAll("SELECT u.id, u.fullname, u.avatar, u.username, COUNT(gp.id) as post_count, COALESCE(SUM(gp.likes_count),0) as total_likes FROM group_posts gp JOIN users u ON gp.user_id = u.id WHERE gp.group_id = ? AND gp.status = 'active' AND gp.created_at BETWEEN ? AND ? GROUP BY u.id ORDER BY total_likes DESC LIMIT 20", [$gid, $sd, $ed]);
        } else {
            $leaders = $d->fetchAll("SELECT u.id, u.fullname, u.avatar, u.username, COUNT(gc.id) as comment_count, COALESCE(SUM(gc.likes_count),0) as total_likes FROM group_post_comments gc JOIN users u ON gc.user_id = u.id JOIN group_posts gp ON gc.post_id = gp.id WHERE gp.group_id = ? AND gc.status = 'active' AND gc.created_at BETWEEN ? AND ? GROUP BY u.id ORDER BY total_likes DESC LIMIT 20", [$gid, $sd, $ed]);
        }
        gOk('OK', ['leaderboard' => $leaders ?: [], 'month' => $month, 'type' => $type]);
    }

    if ($action === 'comments') {
        api_try_cache('grp_comments_' . intval($_GET['post_id'] ?? 0), 15);
        $pid = intval($_GET['post_id'] ?? 0);
        $uid = 0;
        try { $uid = getAuthUserId(); } catch(Throwable $e) {}
        $cmts = $d->fetchAll("SELECT c.*, u.fullname as user_name, u.avatar as user_avatar, u.username FROM group_post_comments c JOIN users u ON c.user_id = u.id WHERE c.post_id = ? AND c.status = 'active' ORDER BY c.created_at ASC", [$pid]);
        if ($cmts && $uid) {
            $cids = array_column($cmts, 'id');
            if ($cids) {
                $ph = implode(',', array_fill(0, count($cids), '?'));
                $liked = $d->fetchAll("SELECT comment_id FROM group_post_comment_likes WHERE user_id = ? AND comment_id IN ($ph)", array_merge([$uid], $cids));
                $likedSet = [];
                foreach ($liked as $l) $likedSet[$l['comment_id']] = true;
                foreach ($cmts as &$cm) {
                    $cm['user_vote'] = isset($likedSet[$cm['id']]) ? 'up' : null;
                    $cm['user_liked'] = isset($likedSet[$cm['id']]);
                }
            }
        }
        gOk('OK', $cmts ?: []);
    }



    if ($action === 'search') {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) gErr('Tu khoa qua ngan');
        $uid = getOptionalAuthUserId();
        $myGroupIds = [];
        if ($uid) { $myGroupIds = array_column($d->fetchAll("SELECT group_id FROM group_members WHERE user_id = ?", [$uid]), 'group_id'); }
        $groups = $d->fetchAll("SELECT g.*, gc.name as cat_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id = gc.id WHERE g.status = 'active' AND (g.name LIKE ? OR g.description LIKE ?) ORDER BY g.member_count DESC LIMIT 20", ["%$q%", "%$q%"]);
        foreach ($groups as &$g) { $g['is_member'] = in_array($g['id'], $myGroupIds); }
        gOk('OK', $groups);
    }
    gErr('Invalid action');
}

if ($method === 'POST') {
    $uid = getAuthUserId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if ($action === 'join') {
        $gid = intval($input['group_id'] ?? 0);
        if (!$gid) gErr('Missing group_id');
        $ex = $d->fetchOne("SELECT id, role FROM group_members WHERE group_id = ? AND user_id = ?", [$gid, $uid]);
        if ($ex) {
            if ($ex['role'] === 'admin') gErr('Admin khong the roi nhom');
            $d->query("DELETE FROM group_members WHERE group_id = ? AND user_id = ?", [$gid, $uid]);
            $d->query("UPDATE `groups` SET member_count = GREATEST(member_count - 1, 0) WHERE id = ?", [$gid]);
            gOk('Da roi nhom', ['joined' => false]);
        } else {
            // Feature gate: check max groups for free users
            $limitErr = checkLimit($uid, 'groups_max');
            if ($limitErr) { gErr($limitErr); }
            $d->query("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')", [$gid, $uid]);
            $d->query("UPDATE `groups` SET member_count = member_count + 1 WHERE id = ?", [$gid]);
            gOk('Da tham gia', ['joined' => true]);
        }
    }

    if ($action === 'post') {
        $gid = intval($input['group_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        if (!$content) gErr('Noi dung trong');
        $member = $d->fetchOne("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?", [$gid, $uid]);
        if (!$member) gErr('Ban chua tham gia nhom nay');
        $imgs = isset($input['images']) ? json_encode($input['images']) : null;
        $d->query("INSERT INTO group_posts (group_id, user_id, content, title, images, type) VALUES (?, ?, ?, ?, ?, ?)", [$gid, $uid, $content, $input['title'] ?? null, $imgs, $input['type'] ?? 'post']);
        $d->query("UPDATE `groups` SET post_count = post_count + 1 WHERE id = ?", [$gid]);
        // Push to group members (max 50)
        try{
            require_once __DIR__.'/../includes/push-helper.php';
            $grp=$d->fetchOne("SELECT name FROM `groups` WHERE id=?",[$gid]);
            $me=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
            $gName=$grp?$grp['name']:'Cộng đồng';
            $mName=$me?$me['fullname']:'Ai đó';
            $preview=mb_substr($content,0,50);
            $members=$d->fetchAll("SELECT user_id FROM group_members WHERE group_id=? AND user_id!=? LIMIT 50",[$gid,$uid]);
            foreach($members as $m){notifyUser($m['user_id'],'Cộng đồng: '.$gName,$mName.': '.$preview,'group','/group.html?id='.$gid);}
        }catch(Throwable $e){}
        gOk('Da dang bai');
    }

    if ($action === 'like_post') {
        $pid = intval($input['post_id'] ?? 0);
        $ex = $d->fetchOne("SELECT id FROM group_post_likes WHERE post_id = ? AND user_id = ?", [$pid, $uid]);
        if ($ex) {
            $d->query("DELETE FROM group_post_likes WHERE post_id = ? AND user_id = ?", [$pid, $uid]);
            $d->query("UPDATE group_posts SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?", [$pid]);
        } else {
            $d->query("INSERT IGNORE INTO group_post_likes (post_id, user_id) VALUES (?, ?)", [$pid, $uid]);
            $d->query("UPDATE group_posts SET likes_count = likes_count + 1 WHERE id = ?", [$pid]);
        }
        $cnt = $d->fetchOne("SELECT likes_count FROM group_posts WHERE id = ?", [$pid]);
        // Push: notify group post author on like (only on like, not unlike)
        if (!$ex) {
            try{
                require_once __DIR__.'/../includes/push-helper.php';
                $gpost=$d->fetchOne("SELECT user_id,group_id FROM group_posts WHERE id=?",[$pid]);
                if($gpost&&intval($gpost['user_id'])!==$uid){
                    $me=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
                    $grp=$d->fetchOne("SELECT name FROM `groups` WHERE id=?",[$gpost['group_id']]);
                    $mName=$me?$me['fullname']:'Ai đó';
                    $gName=$grp?$grp['name']:'Cộng đồng';
                    notifyUser(intval($gpost['user_id']),$gName.': '.$mName.' đã thành công','Bài viết được thành công','group','/group.html?id='.$gpost['group_id']);
                }
            }catch(Throwable $e){}
        }
        gOk('OK', ['liked' => !$ex, 'count' => intval($cnt['likes_count'] ?? 0)]);
    }


    if ($action === 'like_comment') {
        $uid = getAuthUserId();
        $cid = intval($input['comment_id'] ?? 0);
        if (!$cid) { echo json_encode(['success'=>false,'message'=>'Missing comment_id']); exit; }
        $exists = $d->fetchOne("SELECT id FROM group_post_comment_likes WHERE comment_id = ? AND user_id = ?", [$cid, $uid]);
        if ($exists) {
            $d->query("DELETE FROM group_post_comment_likes WHERE comment_id = ? AND user_id = ?", [$cid, $uid]);
            $d->query("UPDATE group_post_comments SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = ?", [$cid]);
            $cnt = $d->fetchOne("SELECT likes_count FROM group_post_comments WHERE id = ?", [$cid]);
            gOk('OK', ['liked' => false, 'likes_count' => $cnt ? $cnt['likes_count'] : 0]);
        } else {
            $d->query("INSERT IGNORE INTO group_post_comment_likes (comment_id, user_id) VALUES (?, ?)", [$cid, $uid]);
            $d->query("UPDATE group_post_comments SET likes_count = likes_count + 1 WHERE id = ?", [$cid]);
            $cnt = $d->fetchOne("SELECT likes_count FROM group_post_comments WHERE id = ?", [$cid]);
            gOk('OK', ['liked' => true, 'likes_count' => $cnt ? $cnt['likes_count'] : 0]);
        }
    }

    if ($action === 'comment') {
        $pid = intval($input['post_id'] ?? 0);
        $content = trim($input['content'] ?? '');
        if (!$content) gErr('Noi dung trong');
        $d->query("INSERT INTO group_post_comments (post_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)", [$pid, $uid, $input['parent_id'] ?? null, $content]);
        $d->query("UPDATE group_posts SET comments_count = comments_count + 1 WHERE id = ?", [$pid]);
        try{
            require_once __DIR__.'/../includes/push-helper.php';
            $gpost=$d->fetchOne("SELECT user_id,group_id FROM group_posts WHERE id=?",[$pid]);
            if($gpost&&intval($gpost['user_id'])!==$uid){
                $me=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);
                $grp=$d->fetchOne("SELECT name FROM `groups` WHERE id=?",[$gpost['group_id']]);
                $mName=$me?$me['fullname']:'Ai đó';
                $gName=$grp?$grp['name']:'Cộng đồng';
                notifyUser(intval($gpost['user_id']),$gName.': '.$mName.' đã ghi chú',mb_substr($content,0,50),'group','/group.html?id='.$gpost['group_id']);
            }
        }catch(Throwable $e){}
        gOk('Da binh luan');
    }

    if ($action === 'create') {
        $name = trim($input['name'] ?? '');
        if (!$name) gErr('Nhap ten nhom');
        $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name));
        $slug = trim($slug, '-') ?: 'group-' . time();
        $ex = $d->fetchOne("SELECT id FROM `groups` WHERE slug = ?", [$slug]);
        if ($ex) $slug .= '-' . time();
        
        $privacy = in_array($input['privacy'] ?? '', ['public','private']) ? $input['privacy'] : 'public';
        $catId = intval($input['category_id'] ?? 0) ?: null;
        $desc = trim($input['description'] ?? '');
        
        // Handle icon upload
        $iconUrl = null;
        if (!empty($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['icon']['size'] <= 5*1024*1024) {
                $dir = __DIR__ . '/../uploads/avatars/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fn = 'grp_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['icon']['tmp_name'], $dir . $fn);
                $iconUrl = '/uploads/avatars/' . $fn;
            }
        }
        
        // Handle banner upload
        $bannerUrl = null;
        if (!empty($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp']) && $_FILES['banner']['size'] <= 10*1024*1024) {
                $dir = __DIR__ . '/../uploads/posts/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fn = 'grp_banner_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['banner']['tmp_name'], $dir . $fn);
                $bannerUrl = '/uploads/posts/' . $fn;
            }
        }
        
        $d->query("INSERT INTO `groups` (name, slug, description, creator_id, category_id, category, banner_color, privacy, icon_image, banner_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
            [$name, $slug, $desc, $uid, $catId, $input['category'] ?? 'general', '#7C3AED', $privacy, $iconUrl, $bannerUrl]);
        $gid = $d->getLastInsertId();
        if (!$gid) { $gid = $d->fetchOne("SELECT MAX(id) as m FROM `groups`", [])['m']; }
        $d->query("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')", [$gid, $uid]);
        $d->query("UPDATE `groups` SET member_count = 1 WHERE id = ?", [$gid]);
        gOk('Da tao nhom', ['id' => intval($gid), 'slug' => $slug]);
    }
    
    // Update group (admin only)
    if ($action === 'update_group') {
        $gid = intval($input['group_id'] ?? ($_POST['group_id'] ?? 0));
        $role = $d->fetchOne("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?", [$gid, $uid]);
        if (!$role || $role['role'] !== 'admin') gErr('Khong co quyen');
        
        $updates = [];
        $params = [];
        
        if (isset($input['name']) && trim($input['name'])) { $updates[] = "name = ?"; $params[] = trim($input['name']); }
        if (isset($input['description'])) { $updates[] = "description = ?"; $params[] = trim($input['description']); }
        if (isset($input['privacy']) && in_array($input['privacy'], ['public','private'])) { $updates[] = "privacy = ?"; $params[] = $input['privacy']; }
        if (isset($input['category_id'])) { $updates[] = "category_id = ?"; $params[] = intval($input['category_id']); }
        if (isset($input['banner_color'])) { $updates[] = "banner_color = ?"; $params[] = $input['banner_color']; }
        
        // Icon upload
        if (!empty($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $dir = __DIR__ . '/../uploads/avatars/';
                $fn = 'grp_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['icon']['tmp_name'], $dir . $fn);
                $updates[] = "icon_image = ?"; $params[] = '/uploads/avatars/' . $fn;
            }
        }
        // Banner upload
        if (!empty($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $dir = __DIR__ . '/../uploads/posts/';
                $fn = 'grp_banner_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['banner']['tmp_name'], $dir . $fn);
                $updates[] = "banner_image = ?"; $params[] = '/uploads/posts/' . $fn;
            }
        }
        
        if (empty($updates)) gErr('Khong co gi de cap nhat');
        $params[] = $gid;
        $d->query("UPDATE `groups` SET " . implode(', ', $updates) . " WHERE id = ?", $params);
        gOk('Da cap nhat nhom');
    }
    gErr('Invalid action');
}

} catch (Throwable $e) {
    error_log("Groups API: " . $e->getMessage());
    gErr('Loi: ' . $e->getMessage(), 500);
}
gErr('Invalid request');
