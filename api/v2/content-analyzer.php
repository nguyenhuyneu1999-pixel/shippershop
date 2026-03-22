<?php
// ShipperShop API v2 — Content Analyzer
// Deep analysis: readability, sentiment, keyword density, engagement prediction
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {

$text=trim($_GET['text']??'');
if($_SERVER['REQUEST_METHOD']==='POST'){$input=json_decode(file_get_contents('php://input'),true);$text=trim($input['text']??$text);}
if(!$text||mb_strlen($text)<10){echo json_encode(['success'=>true,'data'=>['error'=>'Min 10 chars']]);exit;}

$lower=mb_strtolower($text);
$words=preg_split('/\s+/u',$text);
$wordCount=count($words);
$charCount=mb_strlen($text);
$sentences=preg_split('/[.!?]+/u',$text,-1,PREG_SPLIT_NO_EMPTY);
$sentenceCount=max(1,count($sentences));
$avgWordsPerSentence=round($wordCount/$sentenceCount,1);

// Readability (simple Vietnamese readability)
$readability=$avgWordsPerSentence<=15?'de_doc':($avgWordsPerSentence<=25?'trung_binh':'kho_doc');
$readScore=$avgWordsPerSentence<=15?90:($avgWordsPerSentence<=25?60:30);

// Sentiment (simple keyword-based)
$positive=['tot','tuyet','vui','hanh phuc','cam on','yeu','dep','nhanh','chuyen nghiep','an toan','tin cay','chat luong'];
$negative=['te','chan','buon','gian','loi','cham','huy','mat','ho','xau','toi','that bai'];
$posCount=0;$negCount=0;
foreach($positive as $p){if(mb_strpos($lower,$p)!==false)$posCount++;}
foreach($negative as $n){if(mb_strpos($lower,$n)!==false)$negCount++;}
$sentiment=$posCount>$negCount?'positive':($negCount>$posCount?'negative':'neutral');
$sentimentScore=50+($posCount-$negCount)*10;
$sentimentScore=max(0,min(100,$sentimentScore));

// Keyword density
$wordFreq=[];$stopWords=['la','va','cua','cho','trong','voi','nhung','cac','mot','da','duoc','co','khong','den','tu','se','tai','o','di','ve','len','xuong','ra','vao','tren','duoi','day','do','the','nhu','ma','toi','ban','nay','ay','nha','ha','nhe','a','ah'];
foreach($words as $w){$wl=mb_strtolower($w);if(mb_strlen($wl)>=2&&!in_array($wl,$stopWords)){$wordFreq[$wl]=($wordFreq[$wl]??0)+1;}}
arsort($wordFreq);
$topKeywords=array_slice($wordFreq,0,8,true);
$keywords=[];foreach($topKeywords as $w=>$c){$keywords[]=['word'=>$w,'count'=>$c,'density'=>round($c/$wordCount*100,1)];}

// Hashtag + emoji analysis
preg_match_all('/#\w+/u',$text,$hm);$hashtags=$hm[0]??[];
preg_match_all('/[\x{1F600}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u',$text,$em);$emojis=$em[0]??[];

echo json_encode(['success'=>true,'data'=>['word_count'=>$wordCount,'char_count'=>$charCount,'sentence_count'=>$sentenceCount,'avg_words_per_sentence'=>$avgWordsPerSentence,'readability'=>$readability,'read_score'=>$readScore,'sentiment'=>$sentiment,'sentiment_score'=>$sentimentScore,'keywords'=>$keywords,'hashtags'=>$hashtags,'emoji_count'=>count($emojis),'positive_words'=>$posCount,'negative_words'=>$negCount]],JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
