<?php
// ShipperShop API v2 — Polls
// Create polls in posts, vote, results
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
    // Get poll for a post
    if(!$action||$action==='get'){
        $pid=intval($_GET['post_id']??0);
        if(!$pid) pl_fail('Missing post_id');
        $uid=optional_auth();

        $poll=$d->fetchOne("SELECT * FROM polls WHERE post_id=?",[$pid]);
        if(!$poll) pl_ok('OK',null);

        $options=$d->fetchAll("SELECT id,text,vote_count FROM poll_options WHERE poll_id=? ORDER BY id",[$poll['id']]);
        $myVotes=[];
        if($uid){
            $votes=$d->fetchAll("SELECT option_id FROM poll_votes WHERE poll_id=? AND user_id=?",[$poll['id'],$uid]);
            $myVotes=array_column($votes,'option_id');
        }

        $expired=$poll['ends_at']&&strtotime($poll['ends_at'])<time();

        pl_ok('OK',[
            'id'=>intval($poll['id']),
            'question'=>$poll['question'],
            'options'=>$options,
            'total_votes'=>intval($poll['total_votes']),
            'allow_multiple'=>intval($poll['allow_multiple']),
            'my_votes'=>$myVotes,
            'ended'=>$expired,
            'ends_at'=>$poll['ends_at'],
        ]);
    }
    pl_ok('OK',[]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $uid=require_auth();
    $input=json_decode(file_get_contents('php://input'),true);

    // Create poll (attached to a post)
    if($action==='create'){
        $pid=intval($input['post_id']??0);
        $question=trim($input['question']??'');
        $options=$input['options']??[];
        $allowMultiple=intval($input['allow_multiple']??0);
        $hours=intval($input['hours']??0);

        if(!$pid) pl_fail('Missing post_id');
        // Verify post ownership
        $post=$d->fetchOne("SELECT user_id FROM posts WHERE id=?",[$pid]);
        if(!$post||intval($post['user_id'])!==$uid) pl_fail('Not your post',403);
        // Check no existing poll
        if($d->fetchOne("SELECT id FROM polls WHERE post_id=?",[$pid])) pl_fail('Bài này đã có poll');
        if(count($options)<2||count($options)>10) pl_fail('2-10 lựa chọn');

        $endsAt=$hours>0?date('Y-m-d H:i:s',time()+$hours*3600):null;
        $pdo->prepare("INSERT INTO polls (post_id,question,allow_multiple,ends_at,created_at) VALUES (?,?,?,?,NOW())")->execute([$pid,$question?:null,$allowMultiple,$endsAt]);
        $pollId=intval($pdo->lastInsertId());
        if(!$pollId){$r=$pdo->query("SELECT MAX(id) as m FROM polls");$pollId=intval($r->fetch(PDO::FETCH_ASSOC)['m']);}

        foreach($options as $opt){
            $text=trim($opt);
            if($text) $pdo->prepare("INSERT INTO poll_options (poll_id,text) VALUES (?,?)")->execute([$pollId,$text]);
        }

        // Update post type
        $d->query("UPDATE posts SET type='poll' WHERE id=?",[$pid]);

        pl_ok('Đã tạo poll!',['poll_id'=>$pollId]);
    }

    // Vote
    if(!$action||$action==='vote'){
        $pollId=intval($input['poll_id']??0);
        $optionId=intval($input['option_id']??0);
        if(!$pollId||!$optionId) pl_fail('Missing data');

        $poll=$d->fetchOne("SELECT * FROM polls WHERE id=?",[$pollId]);
        if(!$poll) pl_fail('Poll not found',404);
        if($poll['ends_at']&&strtotime($poll['ends_at'])<time()) pl_fail('Poll đã kết thúc');

        // Check option belongs to poll
        $opt=$d->fetchOne("SELECT id FROM poll_options WHERE id=? AND poll_id=?",[$optionId,$pollId]);
        if(!$opt) pl_fail('Invalid option');

        // Check existing vote
        $existing=$d->fetchOne("SELECT id FROM poll_votes WHERE poll_id=? AND option_id=? AND user_id=?",[$pollId,$optionId,$uid]);
        if($existing){
            // Unvote
            $d->query("DELETE FROM poll_votes WHERE id=?",[$existing['id']]);
            $d->query("UPDATE poll_options SET vote_count=GREATEST(vote_count-1,0) WHERE id=?",[$optionId]);
            $d->query("UPDATE polls SET total_votes=GREATEST(total_votes-1,0) WHERE id=?",[$pollId]);
            pl_ok('Đã bỏ phiếu',['action'=>'unvoted']);
        }

        // If not allow_multiple, remove previous vote on other options
        if(!intval($poll['allow_multiple'])){
            $prev=$d->fetchAll("SELECT id,option_id FROM poll_votes WHERE poll_id=? AND user_id=?",[$pollId,$uid]);
            foreach($prev as $pv){
                $d->query("DELETE FROM poll_votes WHERE id=?",[$pv['id']]);
                $d->query("UPDATE poll_options SET vote_count=GREATEST(vote_count-1,0) WHERE id=?",[$pv['option_id']]);
                $d->query("UPDATE polls SET total_votes=GREATEST(total_votes-1,0) WHERE id=?",[$pollId]);
            }
        }

        // Add vote
        $pdo->prepare("INSERT INTO poll_votes (poll_id,option_id,user_id,created_at) VALUES (?,?,?,NOW())")->execute([$pollId,$optionId,$uid]);
        $d->query("UPDATE poll_options SET vote_count=vote_count+1 WHERE id=?",[$optionId]);
        $d->query("UPDATE polls SET total_votes=total_votes+1 WHERE id=?",[$pollId]);

        try{$pdo->prepare("INSERT INTO user_xp (user_id,action,xp,detail,created_at) VALUES (?,'poll_vote',2,'Voted in poll',NOW())")->execute([$uid]);}catch(\Throwable $e){}

        pl_ok('Đã bỏ phiếu!',['action'=>'voted']);
    }

    // Delete poll (post owner only)
    if($action==='delete'){
        $pollId=intval($input['poll_id']??0);
        if(!$pollId) pl_fail('Missing poll_id');
        $poll=$d->fetchOne("SELECT p.post_id,ps.user_id FROM polls p JOIN posts ps ON p.post_id=ps.id WHERE p.id=?",[$pollId]);
        if(!$poll||intval($poll['user_id'])!==$uid) pl_fail('Not allowed',403);
        $d->query("DELETE FROM poll_votes WHERE poll_id=?",[$pollId]);
        $d->query("DELETE FROM poll_options WHERE poll_id=?",[$pollId]);
        $d->query("DELETE FROM polls WHERE id=?",[$pollId]);
        pl_ok('Đã xóa poll');
    }

    pl_fail('Action không hợp lệ');
}

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
