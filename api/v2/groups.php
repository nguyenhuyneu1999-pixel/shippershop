<?php
/**
 * ShipperShop API v2 — Groups
 * Wraps existing + adds: edit, delete, ban_member, set_role, pin_post, invite
 */
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/cache.php';
require_once __DIR__.'/../../includes/rate-limiter.php';
require_once __DIR__.'/../../includes/validator.php';
require_once __DIR__.'/../../includes/upload-handler.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$method=$_SERVER['REQUEST_METHOD'];$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// Helper: check if user is admin/creator of group
function isGroupAdmin($gid, $uid) {
    $d = db();
    $g = $d->fetchOne("SELECT creator_id FROM `groups` WHERE id=?", [$gid]);
    if ($g && intval($g['creator_id']) === $uid) return true;
    $m = $d->fetchOne("SELECT role FROM group_members WHERE group_id=? AND user_id=?", [$gid, $uid]);
    return $m && ($m['role'] === 'admin' || $m['role'] === 'moderator');
}

// ========== GET ==========
if($method==='GET'){
    $uid=optional_auth();

    // Categories
    if($action==='categories'){
        $cats=cache_remember('group_categories',function()use($d){
            return $d->fetchAll("SELECT * FROM group_categories ORDER BY name");
        },300);
        ok('OK',$cats);
    }

    // Discover (list all groups)
    if($action==='discover'||!$action){
        $page=max(1,intval($_GET['page']??1));$limit=min(intval($_GET['limit']??20),50);$offset=($page-1)*$limit;
        $cat=intval($_GET['category_id']??0);$search=trim($_GET['search']??'');
        $w=["1=1"];$p=[];
        if($cat){$w[]="g.category_id=?";$p[]=$cat;}
        if($search){$w[]="(g.name LIKE ? OR g.description LIKE ?)";$p[]='%'.$search.'%';$p[]='%'.$search.'%';}
        $wc=implode(' AND ',$w);
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM `groups` g WHERE $wc",$p)['c']);
        $rows=$d->fetchAll("SELECT g.*,gc.name as category_name,(SELECT COUNT(*) FROM group_members WHERE group_id=g.id) as member_count FROM `groups` g LEFT JOIN group_categories gc ON g.category_id=gc.id WHERE $wc ORDER BY g.member_count DESC LIMIT $limit OFFSET $offset",$p);
        if($uid){
            $gids=array_column($rows,'id');
            if($gids){
                $ph=implode(',',array_fill(0,count($gids),'?'));
                $joined=$d->fetchAll("SELECT group_id FROM group_members WHERE user_id=? AND group_id IN ($ph)",array_merge([$uid],$gids));
                $joinedSet=array_flip(array_column($joined,'group_id'));
                foreach($rows as &$r){$r['is_member']=isset($joinedSet[$r['id']]);}unset($r);
            }
        }
        echo json_encode(['success'=>true,'data'=>['groups'=>$rows,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // Detail
    if($action==='detail'){
        $gid=intval($_GET['id']??0);
        if(!$gid) fail('Missing id');
        $g=$d->fetchOne("SELECT g.*,gc.name as category_name,u.fullname as creator_name FROM `groups` g LEFT JOIN group_categories gc ON g.category_id=gc.id LEFT JOIN users u ON g.creator_id=u.id WHERE g.id=?",[$gid]);
        if(!$g) fail('Không tìm thấy',404);
        $g['member_count']=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_members WHERE group_id=?",[$gid])['c']);
        $g['post_count']=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE group_id=? AND `status`='active'",[$gid])['c']);
        if($uid){
            $m=$d->fetchOne("SELECT role FROM group_members WHERE group_id=? AND user_id=?",[$gid,$uid]);
            $g['is_member']=!!$m;
            $g['my_role']=$m?$m['role']:null;
        }
        $rules=$d->fetchAll("SELECT * FROM group_rules WHERE group_id=? ORDER BY `order`",[$gid]);
        $g['rules']=$rules;
        ok('OK',$g);
    }

    // Posts
    if($action==='posts'){
        $gid=intval($_GET['group_id']??0);$page=max(1,intval($_GET['page']??1));$limit=min(intval($_GET['limit']??20),50);$offset=($page-1)*$limit;
        $sort=$_GET['sort']??'new';
        $ob=$sort==='hot'?'(gp.likes_count*2+gp.comments_count*3) DESC':'gp.created_at DESC';
        $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM group_posts WHERE group_id=? AND `status`='active'",[$gid])['c']);
        $posts=$d->fetchAll("SELECT gp.*,u.fullname as user_name,u.avatar as user_avatar,u.shipping_company FROM group_posts gp LEFT JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.`status`='active' ORDER BY gp.is_pinned DESC,$ob LIMIT $limit OFFSET $offset",[$gid]);
        if($uid&&$posts){
            $pids=array_column($posts,'id');$ph=implode(',',array_fill(0,count($pids),'?'));
            $liked=$d->fetchAll("SELECT post_id FROM group_post_likes WHERE user_id=? AND post_id IN ($ph)",array_merge([$uid],$pids));
            $likedSet=array_flip(array_column($liked,'post_id'));
            foreach($posts as &$p){$p['user_liked']=isset($likedSet[$p['id']]);}unset($p);
        }
        echo json_encode(['success'=>true,'data'=>['posts'=>$posts,'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>max(1,ceil($total/$limit))]]]);exit;
    }

    // Members
    if($action==='members'){
        $gid=intval($_GET['group_id']??0);$limit=min(intval($_GET['limit']??50),100);
        $members=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,u.is_online,gm.role,gm.joined_at FROM group_members gm JOIN users u ON gm.user_id=u.id WHERE gm.group_id=? ORDER BY FIELD(gm.role,'admin','moderator','member') LIMIT $limit",[$gid]);
        ok('OK',$members);
    }

    // Leaderboard
    if($action==='leaderboard'){
        $gid=intval($_GET['group_id']??0);$type=$_GET['type']??'posts';
        if($type==='likes'){
            $lb=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,SUM(gp.likes_count) as score FROM group_posts gp JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.`status`='active' GROUP BY u.id ORDER BY score DESC LIMIT 20",[$gid]);
        }else{
            $lb=$d->fetchAll("SELECT u.id,u.fullname,u.avatar,u.shipping_company,COUNT(gp.id) as score FROM group_posts gp JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.`status`='active' GROUP BY u.id ORDER BY score DESC LIMIT 20",[$gid]);
        }
        ok('OK',$lb);
    }

    // Comments
    if($action==='comments'){
        $pid=intval($_GET['post_id']??0);
        $cmts=$d->fetchAll("SELECT c.*,u.fullname as user_name,u.avatar as user_avatar FROM group_post_comments c LEFT JOIN users u ON c.user_id=u.id WHERE c.post_id=? AND c.`status`='active' ORDER BY c.created_at ASC",[$pid]);
        ok('OK',$cmts);
    }

    // My groups
    if($action==='my_groups'){
        if(!$uid) ok('OK',[]);
        $groups=$d->fetchAll("SELECT g.*,gm.role,(SELECT COUNT(*) FROM group_members WHERE group_id=g.id) as member_count FROM group_members gm JOIN `groups` g ON gm.group_id=g.id WHERE gm.user_id=? ORDER BY g.name",[$uid]);
        ok('OK',$groups);
    }

    // Search
    if($action==='search'){
        $q=trim($_GET['q']??'');$gid=intval($_GET['group_id']??0);
        if(!$q) ok('OK',[]);
        if($gid){
            $posts=$d->fetchAll("SELECT gp.*,u.fullname as user_name FROM group_posts gp LEFT JOIN users u ON gp.user_id=u.id WHERE gp.group_id=? AND gp.`status`='active' AND gp.content LIKE ? ORDER BY gp.created_at DESC LIMIT 20",[$gid,'%'.$q.'%']);
            ok('OK',$posts);
        }
        $groups=$d->fetchAll("SELECT g.*,(SELECT COUNT(*) FROM group_members WHERE group_id=g.id) as member_count FROM `groups` g WHERE g.name LIKE ? OR g.description LIKE ? ORDER BY g.member_count DESC LIMIT 10",['%'.$q.'%','%'.$q.'%']);
        ok('OK',$groups);
    }

    ok('OK',[]);
}

// ========== POST ==========
if($method==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Join/leave group
    if($action==='join'){
        $gid=intval($input['group_id']??0);
        $ex=$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$gid,$uid]);
        if($ex){
            $d->query("DELETE FROM group_members WHERE group_id=? AND user_id=?",[$gid,$uid]);
            $d->query("UPDATE `groups` SET member_count=GREATEST(member_count-1,0) WHERE id=?",[$gid]);
            ok('Đã rời nhóm',['joined'=>false]);
        }else{
            $pdo->prepare("INSERT IGNORE INTO group_members (group_id,user_id,role,joined_at) VALUES (?,?,'member',NOW())")->execute([$gid,$uid]);
            $d->query("UPDATE `groups` SET member_count=member_count+1 WHERE id=?",[$gid]);
            ok('Đã tham gia!',['joined'=>true]);
        }
    }

    // Create group
    if($action==='create'){
        rate_enforce('group_create',3,3600);
        $name=trim($input['name']??'');$desc=trim($input['description']??'');$catId=intval($input['category_id']??0);
        if(!$name||mb_strlen($name)<3) fail('Tên nhóm tối thiểu 3 ký tự');
        $slug=preg_replace('/[^a-z0-9]+/','-',mb_strtolower($name));
        $ins=$pdo->prepare("INSERT INTO `groups` (name,slug,description,category_id,creator_id,member_count,created_at) VALUES (?,?,?,?,?,1,NOW())");
        $ins->execute([$name,$slug,$desc,$catId,$uid]);
        $gid=intval($pdo->lastInsertId());if(!$gid){$r=$pdo->query("SELECT MAX(id) as m FROM `groups`");$gid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        $pdo->prepare("INSERT INTO group_members (group_id,user_id,role,joined_at) VALUES (?,?,'admin',NOW())")->execute([$gid,$uid]);
        cache_del('group_categories');
        ok('Đã tạo nhóm!',['id'=>$gid,'slug'=>$slug]);
    }

    // Create post in group
    if($action==='post'){
        rate_enforce('group_post',20,3600);
        $gid=intval($input['group_id']??0);$content=trim($input['content']??'');
        if(!$gid||!$content) fail('Thiếu thông tin');
        if(!$d->fetchOne("SELECT id FROM group_members WHERE group_id=? AND user_id=?",[$gid,$uid])) fail('Chưa tham gia nhóm');
        $pdo->prepare("INSERT INTO group_posts (group_id,user_id,content,`status`,created_at) VALUES (?,?,?,'active',NOW())")->execute([$gid,$uid,$content]);
        $pid=intval($pdo->lastInsertId());if(!$pid){$r=$pdo->query("SELECT MAX(id) as m FROM group_posts");$pid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}
        try{$d->query("UPDATE users SET total_posts=total_posts+1 WHERE id=?",[$uid]);}catch(\Throwable $e){}
        ok('Đã đăng!',['id'=>$pid]);
    }

    // Like group post
    if($action==='like_post'){
        $pid=intval($input['post_id']??0);
        $ex=$d->fetchOne("SELECT id FROM group_post_likes WHERE post_id=? AND user_id=?",[$pid,$uid]);
        if($ex){
            $d->query("DELETE FROM group_post_likes WHERE post_id=? AND user_id=?",[$pid,$uid]);
            $d->query("UPDATE group_posts SET likes_count=GREATEST(likes_count-1,0) WHERE id=?",[$pid]);
        }else{
            $pdo->prepare("INSERT IGNORE INTO group_post_likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$uid]);
            $d->query("UPDATE group_posts SET likes_count=likes_count+1 WHERE id=?",[$pid]);
        }
        $cnt=$d->fetchOne("SELECT likes_count FROM group_posts WHERE id=?",[$pid]);
        ok('OK',['liked'=>!$ex,'likes_count'=>intval($cnt['likes_count']??0)]);
    }

    // Comment on group post
    if($action==='comment'){
        rate_enforce('group_comment',30,3600);
        $pid=intval($input['post_id']??0);$content=trim($input['content']??'');$parentId=$input['parent_id']??null;
        if(!$pid||!$content) fail('Thiếu thông tin');
        $pdo->prepare("INSERT INTO group_post_comments (post_id,user_id,parent_id,content,`status`,created_at) VALUES (?,?,?,?,'active',NOW())")->execute([$pid,$uid,$parentId,$content]);
        $d->query("UPDATE group_posts SET comments_count=comments_count+1 WHERE id=?",[$pid]);
        ok('OK');
    }

    // Like comment
    if($action==='like_comment'){
        $cid=intval($input['comment_id']??0);
        $ex=$d->fetchOne("SELECT id FROM group_post_comment_likes WHERE comment_id=? AND user_id=?",[$cid,$uid]);
        if($ex){$d->query("DELETE FROM group_post_comment_likes WHERE comment_id=? AND user_id=?",[$cid,$uid]);}
        else{$pdo->prepare("INSERT IGNORE INTO group_post_comment_likes (comment_id,user_id) VALUES (?,?)")->execute([$cid,$uid]);}
        ok('OK',['liked'=>!$ex]);
    }

    // === NEW: Edit group (admin/creator) ===
    if($action==='edit_group'){
        $gid=intval($input['group_id']??0);
        if(!isGroupAdmin($gid,$uid)) fail('Không có quyền',403);
        $fields=[];$params=[];
        if(isset($input['name'])&&mb_strlen(trim($input['name']))>=3){$fields[]="name=?";$params[]=trim($input['name']);}
        if(isset($input['description'])){$fields[]="description=?";$params[]=trim($input['description']);}
        if(isset($input['category_id'])){$fields[]="category_id=?";$params[]=intval($input['category_id']);}
        if(!empty($fields)){$params[]=$gid;$d->query("UPDATE `groups` SET ".implode(',',$fields)." WHERE id=?",$params);}
        ok('Đã cập nhật');
    }

    // Delete group (creator only)
    if($action==='delete_group'){
        $gid=intval($input['group_id']??0);
        $g=$d->fetchOne("SELECT creator_id FROM `groups` WHERE id=?",[$gid]);
        if(!$g||intval($g['creator_id'])!==$uid) fail('Chỉ người tạo mới xóa được',403);
        $d->query("DELETE FROM group_members WHERE group_id=?",[$gid]);
        $d->query("DELETE FROM `groups` WHERE id=?",[$gid]);
        ok('Đã xóa nhóm');
    }

    // Ban member (admin/mod)
    if($action==='ban_member'){
        $gid=intval($input['group_id']??0);$targetId=intval($input['user_id']??0);
        if(!isGroupAdmin($gid,$uid)) fail('Không có quyền',403);
        $d->query("DELETE FROM group_members WHERE group_id=? AND user_id=?",[$gid,$targetId]);
        $d->query("UPDATE `groups` SET member_count=GREATEST(member_count-1,0) WHERE id=?",[$gid]);
        ok('Đã xóa thành viên');
    }

    // Set role (admin only)
    if($action==='set_role'){
        $gid=intval($input['group_id']??0);$targetId=intval($input['user_id']??0);$role=$input['role']??'member';
        if(!in_array($role,['admin','moderator','member'])) fail('Role không hợp lệ');
        $g=$d->fetchOne("SELECT creator_id FROM `groups` WHERE id=?",[$gid]);
        if(!$g||intval($g['creator_id'])!==$uid) fail('Chỉ admin mới đổi role',403);
        $d->query("UPDATE group_members SET role=? WHERE group_id=? AND user_id=?",[$role,$gid,$targetId]);
        ok('OK');
    }

    // Pin/unpin group post
    if($action==='pin_post'){
        $pid=intval($input['post_id']??0);
        $post=$d->fetchOne("SELECT group_id FROM group_posts WHERE id=?",[$pid]);
        if(!$post||!isGroupAdmin(intval($post['group_id']),$uid)) fail('Không có quyền',403);
        $d->query("UPDATE group_posts SET is_pinned=IF(is_pinned=1,0,1) WHERE id=?",[$pid]);
        ok('OK');
    }

    // Delete group post
    if($action==='delete_post'){
        $pid=intval($input['post_id']??0);
        $post=$d->fetchOne("SELECT group_id,user_id FROM group_posts WHERE id=?",[$pid]);
        if(!$post) fail('Not found',404);
        if(intval($post['user_id'])!==$uid&&!isGroupAdmin(intval($post['group_id']),$uid)) fail('Không có quyền',403);
        $d->query("UPDATE group_posts SET `status`='deleted' WHERE id=?",[$pid]);
        ok('Đã xóa');
    }

    // Edit group post (owner only)
    if($action==='edit_post'){
        $pid=intval($input['post_id']??0);$content=trim($input['content']??'');
        $post=$d->fetchOne("SELECT user_id FROM group_posts WHERE id=?",[$pid]);
        if(!$post||intval($post['user_id'])!==$uid) fail('Không có quyền',403);
        if(!$content) fail('Nội dung trống');
        $d->query("UPDATE group_posts SET content=? WHERE id=?",[$content,$pid]);
        ok('Đã sửa');
    }

    // Add group rules
    if($action==='add_rule'){
        $gid=intval($input['group_id']??0);$rule=trim($input['rule']??'');
        if(!isGroupAdmin($gid,$uid)) fail('Không có quyền',403);
        if(!$rule) fail('Rule trống');
        $order=intval($d->fetchOne("SELECT MAX(`order`) as m FROM group_rules WHERE group_id=?",[$gid])['m'])+1;
        $pdo->prepare("INSERT INTO group_rules (group_id,rule,`order`) VALUES (?,?,?)")->execute([$gid,$rule,$order]);
        ok('OK');
    }

    fail('Action không hợp lệ');
}

fail('Method không hỗ trợ',405);
