<?php
/**
 * ShipperShop API v2 — Posts
 * Rewrite with: cache, rate-limit, validation, block filter, edit/report/pin
 */
// session removed: JWT auth only
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

$d=db();
$method=$_SERVER['REQUEST_METHOD'];
$action=$_GET['action']??'';

function ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data]);exit;}
function fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

// ========== GET ==========
if($method==='GET'){
  $uid=optional_auth();

  // Single post
  if(!empty($_GET['id'])){
    $pid=intval($_GET['id']);
    $post=$d->fetchOne("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.username as user_username,u.shipping_company FROM posts p LEFT JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.`status`='active'",[$pid]);
    if(!$post) fail('Bài viết không tồn tại',404);
    // Increment view count
    try{$d->query("UPDATE posts SET view_count=view_count+1 WHERE id=?",[$pid]);}catch(\Throwable $e){}
    // Check liked/saved
    $post['user_liked']=false;$post['user_saved']=false;
    if($uid){
      $post['user_liked']=!!$d->fetchOne("SELECT id FROM likes WHERE post_id=? AND user_id=?",[$pid,$uid]);
      $post['user_saved']=!!$d->fetchOne("SELECT id FROM saved_posts WHERE post_id=? AND user_id=?",[$pid,$uid]);
    }
    ok('OK',$post);
  }

  // Edit history
  if($action==='edit_history'){
    $pid=intval($_GET['post_id']??0);
    if(!$pid) fail('Missing post_id');
    $edits=$d->fetchAll("SELECT pe.*,u.fullname as editor_name FROM post_edits pe LEFT JOIN users u ON pe.edited_by=u.id WHERE pe.post_id=? ORDER BY pe.created_at DESC LIMIT 20",[$pid]);
    ok('OK',$edits);
  }

  // Comments
  if($action==='comments'){
    $pid=intval($_GET['post_id']??0);
    if(!$pid) fail('Missing post_id');
    $cmts=$d->fetchAll("SELECT c.*,u.fullname as user_name,u.avatar as user_avatar FROM comments c LEFT JOIN users u ON c.user_id=u.id WHERE c.post_id=? AND c.`status`='active' ORDER BY c.created_at ASC",[$pid]);
    // Batch check liked
    if($uid && $cmts){
      $cids=array_column($cmts,'id');
      $ph=implode(',',array_fill(0,count($cids),'?'));
      $liked=$d->fetchAll("SELECT comment_id FROM comment_likes WHERE user_id=? AND comment_id IN ($ph)",array_merge([$uid],$cids));
      $likedSet=array_flip(array_column($liked,'comment_id'));
      foreach($cmts as &$c){$c['user_liked']=isset($likedSet[$c['id']]);$c['likes_count']=intval($c['likes_count']??0);}
      unset($c);
    }
    ok('OK',$cmts);
  }

  // Feed
  $page=max(1,intval($_GET['page']??1));
  $limit=min(intval($_GET['limit']??20),50);
  $sort=$_GET['sort']??'hot';
  $offset=($page-1)*$limit;

  $where=["p.`status`='active'"];
  $params=[];
  $joins="LEFT JOIN users u ON p.user_id=u.id";

  // Block filter
  if($uid){
    $joins.=" LEFT JOIN user_blocks ub ON ub.user_id=$uid AND ub.blocked_user_id=p.user_id";
    $where[]="ub.id IS NULL";
  }

  // Following filter
  if($sort==='following' && $uid){
    $joins.=" JOIN follows fw ON fw.following_id=p.user_id AND fw.follower_id=$uid";
  }

  // User filter
  if(!empty($_GET['user_id'])){$where[]="p.user_id=?";$params[]=intval($_GET['user_id']);}
  // Type filter
  if(!empty($_GET['type'])&&$_GET['type']!=='all'){$where[]="p.type=?";$params[]=$_GET['type'];}
  // Search
  if(!empty($_GET['search'])){$where[]="p.content LIKE ?";$params[]='%'.sanitize($_GET['search']).'%';}
  // Company
  if(!empty($_GET['company'])){$where[]="u.shipping_company=?";$params[]=sanitize($_GET['company']);}
  // Hashtag
  if(!empty($_GET['hashtag'])){$where[]="p.content LIKE ?";$params[]='%#'.sanitize($_GET['hashtag']).'%';}
  // Province (normalize)
  if(!empty($_GET['province'])){
    $prov=trim($_GET['province']);
    $stripped=preg_replace('/^(Thành phố |Tỉnh |TP\.\s*)/u','',$prov);
    $where[]="(p.province=? OR p.province=? OR p.province=?)";
    $params[]=$prov;$params[]=$stripped;$params[]="TP. ".$stripped;
  }
  // District (normalize)
  if(!empty($_GET['district'])){
    $dist=trim($_GET['district']);
    $ds=preg_replace('/^(Quận |Huyện |Thị xã |Thành phố )/u','',$dist);
    $where[]="(p.district=? OR p.district=?)";
    $params[]=$dist;$params[]=$ds;
  }
  // Ward
  if(!empty($_GET['ward'])){$where[]="p.ward=?";$params[]=$_GET['ward'];}

  $wc=implode(' AND ',$where);

  // Count
  $total=intval($d->fetchOne("SELECT COUNT(*) as c FROM posts p $joins WHERE $wc",$params)['c']);
  $totalPages=max(1,ceil($total/$limit));

  // Sort
  switch($sort){
    case 'new': $ob="p.created_at DESC"; break;
    case 'trending': $where[]="p.created_at > DATE_SUB(NOW(),INTERVAL 7 DAY)"; $wc=implode(' AND ',$where); $ob="p.likes_count DESC"; break;
    case 'following': $ob="p.created_at DESC"; break;
    case 'hot': default: $ob="(p.likes_count*2+p.comments_count*3+IF(TIMESTAMPDIFF(HOUR,p.created_at,NOW())<24,100,0)+IF(p.is_pinned=1,500,0)) DESC"; break;
  }

  $posts=$d->fetchAll("SELECT p.*,u.fullname as user_name,u.avatar as user_avatar,u.username as user_username,u.shipping_company FROM posts p $joins WHERE $wc ORDER BY $ob LIMIT $limit OFFSET $offset",$params);

  // Batch liked/saved
  if($uid && $posts){
    $pids=array_column($posts,'id');
    $ph=implode(',',array_fill(0,count($pids),'?'));
    $lk=$d->fetchAll("SELECT post_id FROM likes WHERE user_id=? AND post_id IN ($ph)",array_merge([$uid],$pids));
    $lkSet=array_flip(array_column($lk,'post_id'));
    $sv=$d->fetchAll("SELECT post_id FROM saved_posts WHERE user_id=? AND post_id IN ($ph)",array_merge([$uid],$pids));
    $svSet=array_flip(array_column($sv,'post_id'));
    foreach($posts as &$p){$p['user_liked']=isset($lkSet[$p['id']]);$p['user_saved']=isset($svSet[$p['id']]);}
    unset($p);
  }

  echo json_encode(['success'=>true,'data'=>[
    'posts'=>$posts,
    'meta'=>['page'=>$page,'per_page'=>$limit,'total'=>$total,'total_pages'=>$totalPages]
  ]]);exit;
}

// ========== POST ==========
if($method==='POST'){
  $uid=require_auth();
  $input=json_decode(file_get_contents('php://input'),true);
  $pdo=$d->getConnection();
  $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

  // === CREATE POST ===
  if(!$action || $action==='create'){
    rate_enforce('post_create',10,3600);
    $content=trim($input['content']??'');
    if(mb_strlen($content)<3) fail('Nội dung tối thiểu 3 ký tự');
    if(mb_strlen($content)>5000) fail('Nội dung tối đa 5000 ký tự');
    $type=sanitize($input['type']??'post');
    $province=sanitize($input['province']??'');
    $district=sanitize($input['district']??'');
    $ward=sanitize($input['ward']??'');

    $ins=$pdo->prepare("INSERT INTO posts (user_id,content,type,province,district,ward,`status`,created_at) VALUES (?,?,?,?,?,?,'active',NOW())");
    $ins->execute([$uid,$content,$type,$province,$district,$ward]);
    $pid=intval($pdo->lastInsertId());
    if(!$pid){$r=$pdo->query("SELECT MAX(id) as m FROM posts");$pid=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

    // Handle images
    if(!empty($_FILES['images'])){
      $imgs=[];
      $files=$_FILES['images'];
      $count=is_array($files['name'])?count($files['name']):1;
      for($i=0;$i<min($count,10);$i++){
        $f=['name'=>is_array($files['name'])?$files['name'][$i]:$files['name'],
            'type'=>is_array($files['type'])?$files['type'][$i]:$files['type'],
            'tmp_name'=>is_array($files['tmp_name'])?$files['tmp_name'][$i]:$files['tmp_name'],
            'error'=>is_array($files['error'])?$files['error'][$i]:$files['error'],
            'size'=>is_array($files['size'])?$files['size'][$i]:$files['size']];
        $up=handle_upload($f,'posts',['user_id'=>$uid,'resize_max'=>1920]);
        if($up['success']) $imgs[]=$up['url'];
      }
      if($imgs) $pdo->prepare("UPDATE posts SET images=? WHERE id=?")->execute([json_encode($imgs),$pid]);
    }

    // Update user stats
    try{$d->query("UPDATE users SET total_posts=total_posts+1 WHERE id=?",[$uid]);}catch(\Throwable $e){}

    ok('Đã đăng bài!',['id'=>$pid]);
  }

  // === EDIT POST ===
  if($action==='edit'){
    $pid=intval($input['post_id']??0);
    if(!$pid) fail('Missing post_id');
    $post=$d->fetchOne("SELECT user_id,content FROM posts WHERE id=? AND `status`='active'",[$pid]);
    if(!$post) fail('Không tìm thấy',404);
    if(intval($post['user_id'])!==$uid) fail('Không có quyền',403);
    $content=trim($input['content']??'');
    if(mb_strlen($content)<3) fail('Nội dung tối thiểu 3 ký tự');
    // Save edit history
    try{$pdo->prepare("INSERT INTO post_edits (post_id,old_content,new_content,edited_by,created_at) VALUES (?,?,?,?,NOW())")->execute([$pid,$post['content'],$content,$uid]);}catch(\Throwable $e){}
    $d->query("UPDATE posts SET content=?,edited_at=NOW() WHERE id=?",[$content,$pid]);
    if(!empty($input['type'])) $d->query("UPDATE posts SET type=? WHERE id=?",[sanitize($input['type']),$pid]);
    ok('Đã sửa bài!');
  }

  // === PIN/UNPIN POST ===
  if($action==='pin'){
    $pid=intval($input['post_id']??0);
    $post=$d->fetchOne("SELECT user_id,is_pinned FROM posts WHERE id=?",[$pid]);
    if(!$post) fail('Không tìm thấy',404);
    $user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(intval($post['user_id'])!==$uid && ($user['role']??'')!=='admin') fail('Không có quyền',403);
    $pinned=intval($post['is_pinned'])?0:1;
    if($pinned){
      // Unpin others first (max 1 pinned per user)
      $d->query("UPDATE posts SET is_pinned=0 WHERE user_id=? AND is_pinned=1",[intval($post['user_id'])]);
    }
    $d->query("UPDATE posts SET is_pinned=? WHERE id=?",[$pinned,$pid]);
    ok($pinned?'Đã ghim bài':'Đã bỏ ghim',['pinned'=>(bool)$pinned]);
  }

  // === DELETE POST ===
  if($action==='delete'){
    $pid=intval($input['post_id']??0);
    $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
    if(!$post) fail('Không tìm thấy',404);
    $user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(intval($post['user_id'])!==$uid && ($user['role']??'')!=='admin') fail('Không có quyền',403);
    $d->query("UPDATE posts SET `status`='deleted' WHERE id=?",[$pid]);
    try{$d->query("UPDATE users SET total_posts=GREATEST(total_posts-1,0) WHERE id=?",[intval($post['user_id'])]);}catch(\Throwable $e){}
    ok('Đã xóa');
  }

  // === VOTE (like/unlike) ===
  if($action==='vote'){
    $pid=intval($input['post_id']??0);
    if(!$pid) fail('Missing post_id');
    $ex=$d->fetchOne("SELECT id FROM likes WHERE post_id=? AND user_id=?",[$pid,$uid]);
    if($ex){
      $d->query("DELETE FROM likes WHERE post_id=? AND user_id=?",[$pid,$uid]);
    }else{
      $pdo->prepare("INSERT IGNORE INTO likes (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$uid]);
    }
    $score=intval($d->fetchOne("SELECT COUNT(*) as c FROM likes WHERE post_id=?",[$pid])['c']);
    $d->query("UPDATE posts SET likes_count=? WHERE id=?",[$score,$pid]);
    // Sync user total_success
    $postOwner=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
    if($postOwner){
      try{
        $ts=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM posts WHERE user_id=? AND `status`='active'",[$postOwner['user_id']]);
        $gs=$d->fetchOne("SELECT COALESCE(SUM(likes_count),0) as s FROM group_posts WHERE user_id=? AND `status`='active'",[$postOwner['user_id']]);
        $d->query("UPDATE users SET total_success=? WHERE id=?",[intval($ts['s'])+intval($gs['s']),$postOwner['user_id']]);
      }catch(\Throwable $e){}
    }
    // Notification
    if(!$ex && $postOwner && intval($postOwner['user_id'])!==$uid){
      try{require_once __DIR__.'/../../includes/push-helper.php';$me=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);notifyUser(intval($postOwner['user_id']),($me?$me['fullname']:'Ai đó').' đã thành công bài viết','','post','/post-detail.html?id='.$pid);}catch(\Throwable $e){}
    }
    ok('OK',['score'=>$score,'user_vote'=>$ex?null:'up']);
  }

  // === COMMENT ===
  if($action==='comment'){
    rate_enforce('comment',30,3600);
    $pid=intval($input['post_id']??0);
    $content=trim($input['content']??'');
    $parentId=$input['parent_id']??null;
    if(!$pid||!$content) fail('Thiếu thông tin');
    if(mb_strlen($content)>2000) fail('Tối đa 2000 ký tự');
    $d->query("INSERT INTO comments (post_id,user_id,parent_id,content,`status`,created_at) VALUES (?,?,?,?,'active',NOW())",[$pid,$uid,$parentId,$content]);
    $d->query("UPDATE posts SET comments_count=comments_count+1 WHERE id=?",[$pid]);
    // Notification
    try{require_once __DIR__.'/../../includes/push-helper.php';$post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);if($post&&intval($post['user_id'])!==$uid){$me=$d->fetchOne("SELECT fullname FROM users WHERE id=?",[$uid]);notifyUser(intval($post['user_id']),'Ghi chú mới',($me?$me['fullname']:'Ai đó').': '.mb_substr($content,0,50),'post','/post-detail.html?id='.$pid);}}catch(\Throwable $e){}
    ok('Đã ghi chú!');
  }

  // === VOTE COMMENT ===
  if($action==='vote_comment'){
    $cid=intval($input['comment_id']??0);
    if(!$cid) fail('Missing comment_id');
    $ex=$d->fetchOne("SELECT id FROM comment_likes WHERE comment_id=? AND user_id=?",[$cid,$uid]);
    if($ex){
      $d->query("DELETE FROM comment_likes WHERE comment_id=? AND user_id=?",[$cid,$uid]);
      $d->query("UPDATE comments SET likes_count=GREATEST(likes_count-1,0) WHERE id=?",[$cid]);
    }else{
      $pdo->prepare("INSERT IGNORE INTO comment_likes (comment_id,user_id) VALUES (?,?)")->execute([$cid,$uid]);
      $d->query("UPDATE comments SET likes_count=likes_count+1 WHERE id=?",[$cid]);
    }
    $cnt=$d->fetchOne("SELECT likes_count FROM comments WHERE id=?",[$cid]);
    ok('OK',['liked'=>!$ex,'likes_count'=>intval($cnt['likes_count']??0)]);
  }

  // === REPORT ===
  if($action==='report'){
    $pid=intval($input['post_id']??0);
    $reason=$input['reason']??'other';
    if(!in_array($reason,['spam','inappropriate','harassment','misinformation','other'])) $reason='other';
    $detail=trim($input['detail']??'');
    if(!$pid) fail('Missing post_id');
    $ex=$d->fetchOne("SELECT id FROM post_reports WHERE post_id=? AND user_id=?",[$pid,$uid]);
    if($ex) fail('Bạn đã báo cáo bài này');
    try{
      $pdo->prepare("INSERT INTO post_reports (post_id,user_id,reason,detail,`status`,created_at) VALUES (?,?,?,?,'pending',NOW())")->execute([$pid,$uid,$reason,$detail]);
      $d->query("UPDATE posts SET report_count=report_count+1 WHERE id=?",[$pid]);
      $rc=$d->fetchOne("SELECT report_count FROM posts WHERE id=?",[$pid]);
      if($rc && intval($rc['report_count'])>=5){
        $d->query("UPDATE posts SET `status`='hidden' WHERE id=?",[$pid]);
      }
    }catch(\Throwable $e){fail('Report error: '.$e->getMessage());}
    ok('Đã báo cáo. Cảm ơn bạn!');
  }

  // === SAVE/UNSAVE ===
  if($action==='save'){
    $pid=intval($input['post_id']??0);
    if(!$pid) fail('Missing post_id');
    $ex=$d->fetchOne("SELECT id FROM saved_posts WHERE post_id=? AND user_id=?",[$pid,$uid]);
    if($ex){$d->query("DELETE FROM saved_posts WHERE post_id=? AND user_id=?",[$pid,$uid]);ok('OK',['saved'=>false]);}
    else{$pdo->prepare("INSERT IGNORE INTO saved_posts (post_id,user_id,created_at) VALUES (?,?,NOW())")->execute([$pid,$uid]);ok('OK',['saved'=>true]);}
  }

  // === SHARE ===
  if($action==='share'){
    $pid=intval($input['post_id']??0);
    if($pid){$d->query("UPDATE posts SET shares_count=shares_count+1 WHERE id=?",[$pid]);$cnt=$d->fetchOne("SELECT shares_count FROM posts WHERE id=?",[$pid]);}
    ok('OK',['shares_count'=>intval($cnt['shares_count']??0)]);
  }

  // === PIN (admin) ===
  if($action==='pin'){
    $user=$d->fetchOne("SELECT role FROM users WHERE id=?",[$uid]);
    if(($user['role']??'')!=='admin') fail('Admin only',403);
    $pid=intval($input['post_id']??0);
    $d->query("UPDATE posts SET is_pinned=IF(is_pinned=1,0,1) WHERE id=?",[$pid]);
    ok('OK');
  }

  fail('Action không hợp lệ');
}

fail('Method không hỗ trợ',405);
