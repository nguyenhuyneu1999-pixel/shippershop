<?php
// ShipperShop API v2 — Post Polls
// Create polls in posts, vote, view results
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$d=db();$pdo=$d->getConnection();$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$action=$_GET['action']??'';

function pl_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function pl_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

if($_SERVER['REQUEST_METHOD']==='GET'){
    $uid=optional_auth();

    // Get poll for a post
    if($action==='get'||!$action){
        $postId=intval($_GET['post_id']??0);
        if(!$postId) pl_fail('Missing post_id');
        $poll=$d->fetchOne("SELECT * FROM post_polls WHERE post_id=?",[$postId]);
        if(!$poll) pl_ok('OK',null);

        $options=$d->fetchAll("SELECT * FROM poll_options WHERE poll_id=? ORDER BY id",[$poll['id']]);
        $poll['options']=$options;
        $poll['expired']=$poll['expires_at']&&strtotime($poll['expires_at'])<time();
        $poll['my_vote']=null;
        if($uid){
            $vote=$d->fetchOne("SELECT option_id FROM poll_votes WHERE poll_id=? AND user_id=?",[$poll['id'],$uid]);
            if($vote) $poll['my_vote']=intval($vote['option_id']);
        }
        pl_ok('OK',$poll);
    }

    pl_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create poll
    if($action==='create'){
        $postId=intval($input['post_id']??0);
        $question=trim($input['question']??'');
        $options=$input['options']??[];
        $hours=intval($input['hours']??0);

        if(!$postId) pl_fail('Missing post_id');
        if(!$question) pl_fail('Nhập câu hỏi');
        if(!is_array($options)||count($options)<2||count($options)>6) pl_fail('Cần 2-6 lựa chọn');

        // Verify post ownership
        $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$postId]);
        if(!$post||intval($post['user_id'])!==$uid) pl_fail('Không có quyền',403);

        // Check no existing poll
        $existing=$d->fetchOne("SELECT id FROM post_polls WHERE post_id=?",[$postId]);
        if($existing) pl_fail('Bài này đã có poll');

        $expiresAt=$hours>0?date('Y-m-d H:i:s',time()+$hours*3600):null;
        $pdo->prepare("INSERT INTO post_polls (post_id,question,expires_at,created_at) VALUES (?,?,?,NOW())")->execute([$postId,$question,$expiresAt]);
        $pollId=intval($pdo->lastInsertId());
        if(!$pollId){$r=$pdo->query("SELECT MAX(id) as m FROM post_polls");$pollId=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

        foreach($options as $opt){
            $text=trim($opt);
            if($text) $pdo->prepare("INSERT INTO poll_options (poll_id,text) VALUES (?,?)")->execute([$pollId,$text]);
        }

        pl_ok('Đã tạo poll!',['poll_id'=>$pollId]);
    }

    // Vote
    if($action==='vote'){
        $pollId=intval($input['poll_id']??0);
        $optionId=intval($input['option_id']??0);
        if(!$pollId||!$optionId) pl_fail('Missing data');

        // Check poll exists and not expired
        $poll=$d->fetchOne("SELECT * FROM post_polls WHERE id=?",[$pollId]);
        if(!$poll) pl_fail('Poll không tồn tại',404);
        if($poll['expires_at']&&strtotime($poll['expires_at'])<time()) pl_fail('Poll đã hết hạn');

        // Check option belongs to poll
        $opt=$d->fetchOne("SELECT id FROM poll_options WHERE id=? AND poll_id=?",[$optionId,$pollId]);
        if(!$opt) pl_fail('Lựa chọn không hợp lệ');

        // Check already voted
        $existing=$d->fetchOne("SELECT id,option_id FROM poll_votes WHERE poll_id=? AND user_id=?",[$pollId,$uid]);
        if($existing){
            if(intval($existing['option_id'])===$optionId){
                // Remove vote
                $d->query("DELETE FROM poll_votes WHERE id=?",[$existing['id']]);
                $d->query("UPDATE poll_options SET vote_count=GREATEST(vote_count-1,0) WHERE id=?",[$optionId]);
                $d->query("UPDATE post_polls SET total_votes=GREATEST(total_votes-1,0) WHERE id=?",[$pollId]);
                pl_ok('Đã bỏ phiếu',['voted'=>false]);
            }else{
                // Change vote
                $d->query("UPDATE poll_options SET vote_count=GREATEST(vote_count-1,0) WHERE id=?",[intval($existing['option_id'])]);
                $d->query("UPDATE poll_votes SET option_id=? WHERE id=?",[$optionId,$existing['id']]);
                $d->query("UPDATE poll_options SET vote_count=vote_count+1 WHERE id=?",[$optionId]);
                pl_ok('Đã đổi phiếu',['voted'=>true,'option_id'=>$optionId]);
            }
        }else{
            // New vote
            $pdo->prepare("INSERT INTO poll_votes (poll_id,option_id,user_id,created_at) VALUES (?,?,?,NOW())")->execute([$pollId,$optionId,$uid]);
            $d->query("UPDATE poll_options SET vote_count=vote_count+1 WHERE id=?",[$optionId]);
            $d->query("UPDATE post_polls SET total_votes=total_votes+1 WHERE id=?",[$pollId]);
            pl_ok('Đã bình chọn!',['voted'=>true,'option_id'=>$optionId]);
        }
    }

    // Delete poll (owner only)
    if($action==='delete'){
        $pollId=intval($input['poll_id']??0);
        $poll=$d->fetchOne("SELECT pp.id,p.user_id FROM post_polls pp JOIN posts p ON pp.post_id=p.id WHERE pp.id=?",[$pollId]);
        if(!$poll||intval($poll['user_id'])!==$uid) pl_fail('Không có quyền',403);
        $d->query("DELETE FROM poll_votes WHERE poll_id=?",[$pollId]);
        $d->query("DELETE FROM poll_options WHERE poll_id=?",[$pollId]);
        $d->query("DELETE FROM post_polls WHERE id=?",[$pollId]);
        pl_ok('Đã xóa poll');
    }

    pl_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
