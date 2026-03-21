<?php
// ShipperShop API v2 — Link Preview (OG metadata)
// Fetches Open Graph tags from URLs for rich link previews in posts
session_start();
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/cache.php';
require_once __DIR__.'/../../includes/rate-limiter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if($_SERVER['REQUEST_METHOD']==='OPTIONS'){http_response_code(204);exit;}

function lp_ok($msg,$data=null){echo json_encode(['success'=>true,'message'=>$msg,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;}
function lp_fail($msg,$code=400){http_response_code($code);echo json_encode(['success'=>false,'message'=>$msg]);exit;}

try {

$url=trim($_GET['url']??'');
if(!$url||!filter_var($url,FILTER_VALIDATE_URL)) lp_fail('URL không hợp lệ');

// Block internal URLs
$host=parse_url($url,PHP_URL_HOST);
if($host&&(strpos($host,'shippershop.vn')!==false||strpos($host,'localhost')!==false||filter_var($host,FILTER_VALIDATE_IP))) lp_fail('URL not allowed');

rate_enforce('link_preview',30,60);

// Check cache first
$cacheKey='lp_'.md5($url);
$cached=cache_get($cacheKey);
if($cached){lp_ok('OK',json_decode($cached,true));exit;}

// Fetch page
$ctx=stream_context_create(['http'=>[
    'method'=>'GET',
    'header'=>"User-Agent: ShipperShop/2.0 LinkPreview\r\nAccept: text/html\r\n",
    'timeout'=>5,
    'follow_location'=>1,
    'max_redirects'=>3,
    'ignore_errors'=>true,
]]);
$html=@file_get_contents($url,false,$ctx);
if(!$html||strlen($html)<100) lp_fail('Không thể tải trang');

// Limit to first 50KB
$html=substr($html,0,51200);

// Extract OG tags
$meta=['url'=>$url,'title'=>'','description'=>'','image'=>'','site_name'=>'','type'=>''];

// og:title
if(preg_match('/<meta[^>]*property=["\']og:title["\'][^>]*content=["\']([^"\']*)["\']/',$html,$m)) $meta['title']=$m[1];
elseif(preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']og:title["\']/',$html,$m)) $meta['title']=$m[1];
elseif(preg_match('/<title[^>]*>([^<]*)<\/title>/',$html,$m)) $meta['title']=trim($m[1]);

// og:description
if(preg_match('/<meta[^>]*property=["\']og:description["\'][^>]*content=["\']([^"\']*)["\']/',$html,$m)) $meta['description']=$m[1];
elseif(preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']og:description["\']/',$html,$m)) $meta['description']=$m[1];
elseif(preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']*)["\']/',$html,$m)) $meta['description']=$m[1];

// og:image
if(preg_match('/<meta[^>]*property=["\']og:image["\'][^>]*content=["\']([^"\']*)["\']/',$html,$m)) $meta['image']=$m[1];
elseif(preg_match('/<meta[^>]*content=["\']([^"\']*)["\'][^>]*property=["\']og:image["\']/',$html,$m)) $meta['image']=$m[1];

// og:site_name
if(preg_match('/<meta[^>]*property=["\']og:site_name["\'][^>]*content=["\']([^"\']*)["\']/',$html,$m)) $meta['site_name']=$m[1];

// Decode HTML entities
foreach($meta as $k=>$v) $meta[$k]=html_entity_decode($v,ENT_QUOTES,'UTF-8');

// Fix relative image URLs
if($meta['image']&&!preg_match('/^https?:\/\//',$meta['image'])){
    $base=parse_url($url);
    $meta['image']=($base['scheme']??'https').'://'.($base['host']??'').$meta['image'];
}

// Cache 1 hour
cache_set($cacheKey,json_encode($meta),3600);

lp_ok('OK',$meta);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
