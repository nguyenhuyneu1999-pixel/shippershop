<?php
// ShipperShop API v2 — Area Coverage Map
// Shipper coverage areas with heatmap density + gap analysis
// session removed: JWT auth only
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$d=db();

try {

$action=$_GET['action']??'';

if(!$action||$action==='overview'){
    $data=cache_remember('area_coverage', function() use($d) {
        $provinces=$d->fetchAll("SELECT province,COUNT(DISTINCT user_id) as shippers,COUNT(*) as posts FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!='' GROUP BY province ORDER BY shippers DESC");
        $totalProvinces=count($provinces);
        $totalShippers=intval($d->fetchOne("SELECT COUNT(DISTINCT user_id) as c FROM posts WHERE `status`='active' AND province IS NOT NULL")['c']);

        // Coverage density: shippers per province
        $highDensity=array_filter($provinces,function($p){return intval($p['shippers'])>=10;});
        $lowDensity=array_filter($provinces,function($p){return intval($p['shippers'])<=2;});

        // Top districts
        $districts=$d->fetchAll("SELECT province,district,COUNT(DISTINCT user_id) as shippers FROM posts WHERE `status`='active' AND district IS NOT NULL AND district!='' GROUP BY province,district ORDER BY shippers DESC LIMIT 20");

        return ['provinces'=>$provinces,'districts'=>$districts,'stats'=>['total_provinces'=>$totalProvinces,'total_shippers'=>$totalShippers,'high_density'=>count($highDensity),'low_density'=>count($lowDensity)]];
    }, 900);
    echo json_encode(['success'=>true,'data'=>$data],JSON_UNESCAPED_UNICODE);exit;
}

// Gap analysis: provinces with no shippers
if($action==='gaps'){
    $allProvinces=['An Giang','Ba Ria - Vung Tau','Bac Giang','Bac Kan','Bac Lieu','Bac Ninh','Ben Tre','Binh Dinh','Binh Duong','Binh Phuoc','Binh Thuan','Ca Mau','Cao Bang','Can Tho','Da Nang','Dak Lak','Dak Nong','Dien Bien','Dong Nai','Dong Thap','Gia Lai','Ha Giang','Ha Nam','Ha Noi','Ha Tinh','Hai Duong','Hai Phong','Hau Giang','Hoa Binh','Hung Yen','Khanh Hoa','Kien Giang','Kon Tum','Lai Chau','Lam Dong','Lang Son','Lao Cai','Long An','Nam Dinh','Nghe An','Ninh Binh','Ninh Thuan','Phu Tho','Phu Yen','Quang Binh','Quang Nam','Quang Ngai','Quang Ninh','Quang Tri','Soc Trang','Son La','Tay Ninh','Thai Binh','Thai Nguyen','Thanh Hoa','Thua Thien Hue','Tien Giang','TP Ho Chi Minh','Tra Vinh','Tuyen Quang','Vinh Long','Vinh Phuc','Yen Bai'];
    $covered=$d->fetchAll("SELECT DISTINCT province FROM posts WHERE `status`='active' AND province IS NOT NULL AND province!=''");
    $coveredList=array_column($covered,'province');
    $gaps=array_values(array_diff($allProvinces,$coveredList));
    echo json_encode(['success'=>true,'data'=>['gaps'=>$gaps,'gap_count'=>count($gaps),'covered'=>count($coveredList),'total'=>count($allProvinces)]],JSON_UNESCAPED_UNICODE);exit;
}

echo json_encode(['success'=>true,'data'=>[]]);

} catch (\Throwable $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
