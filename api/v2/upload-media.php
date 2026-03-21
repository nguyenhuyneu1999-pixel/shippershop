<?php
// ShipperShop API v2 — Universal Media Upload
// Upload images/videos for posts, stories, messages, avatars
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/auth-v2.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

$uid=require_auth();
$type=$_POST['type']??$_GET['type']??'post'; // post, story, message, avatar, traffic

$validTypes=['post','story','message','avatar','traffic','listing'];
if(!in_array($type,$validTypes)){
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'Invalid type']);exit;
}

$dirs=['post'=>'posts','story'=>'stories','message'=>'messages','avatar'=>'avatars','traffic'=>'traffic','listing'=>'marketplace'];
$uploadDir=__DIR__.'/../../uploads/'.$dirs[$type].'/';
if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);

$maxSize=['post'=>5242880,'story'=>5242880,'message'=>10485760,'avatar'=>2097152,'traffic'=>3145728,'listing'=>5242880];
$allowedExt=['jpg','jpeg','png','gif','webp'];
if($type==='message') $allowedExt[]='mp4';
if($type==='story') $allowedExt[]='mp4';

rate_enforce('upload_'.$type,20,3600);

$files=$_FILES['file']??$_FILES['image']??$_FILES['media']??null;
if(!$files||$files['error']===UPLOAD_ERR_NO_FILE){
    header('Content-Type: application/json');
    echo json_encode(['success'=>false,'message'=>'No file uploaded']);exit;
}

// Handle multiple files
$uploaded=[];
$fileArray=is_array($files['name'])?$files:['name'=>[$files['name']],'tmp_name'=>[$files['tmp_name']],'size'=>[$files['size']],'error'=>[$files['error']],'type'=>[$files['type']]];

for($i=0;$i<count($fileArray['name']);$i++){
    if($fileArray['error'][$i]!==UPLOAD_ERR_OK) continue;
    $name=$fileArray['name'][$i];
    $tmpName=$fileArray['tmp_name'][$i];
    $size=$fileArray['size'][$i];

    if($size>$maxSize[$type]){
        $uploaded[]=['error'=>'File quá lớn: '.basename($name)];continue;
    }

    $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(!in_array($ext,$allowedExt)){
        $uploaded[]=['error'=>'Định dạng không hỗ trợ: '.$ext];continue;
    }

    // Generate unique filename
    $newName=time().'_'.mt_rand(1000,9999).'_'.$uid.'.'.$ext;
    $dest=$uploadDir.$newName;

    if(move_uploaded_file($tmpName,$dest)){
        $url='/uploads/'.$dirs[$type].'/'.$newName;

        // If avatar, update user record
        if($type==='avatar'){
            db()->query("UPDATE users SET avatar=? WHERE id=?",[$url,$uid]);
        }

        $uploaded[]=['url'=>$url,'filename'=>$newName,'size'=>$size,'type'=>$ext];
    }else{
        $uploaded[]=['error'=>'Upload failed: '.basename($name)];
    }
}

// Audit
try{db()->query("INSERT INTO audit_log (user_id,action,detail,ip,created_at) VALUES (?,'upload',?,?,NOW())",[$uid,$type.': '.count($uploaded).' files',$_SERVER['REMOTE_ADDR']??'']);}catch(\Throwable $e){}

header('Content-Type: application/json');
echo json_encode(['success'=>true,'data'=>['files'=>$uploaded,'count'=>count($uploaded)]],JSON_UNESCAPED_UNICODE);
