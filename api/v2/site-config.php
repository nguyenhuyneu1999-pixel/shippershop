<?php
// ShipperShop API v2 — Site Configuration (public)
// Returns site name, version, feature flags, shipping companies
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

echo json_encode([
    'success' => true,
    'data' => [
        'site' => [
            'name' => 'ShipperShop',
            'url' => 'https://shippershop.vn',
            'version' => '8.2.0',
            'tagline' => 'Cộng đồng shipper Việt Nam',
            'logo_text' => 'ShipperShop',
            'primary_color' => '#7C3AED',
        ],
        'features' => [
            'stories' => true,
            'reactions' => true,
            'dark_mode' => true,
            'groups' => true,
            'marketplace' => true,
            'wallet' => true,
            'gamification' => true,
            'traffic_alerts' => true,
            'push_notifications' => true,
            'group_chat' => true,
            'post_scheduling' => true,
            'verification' => true,
        ],
        'shipping_companies' => [
            ['name'=>'GHTK','color'=>'#00b14f'],
            ['name'=>'GHN','color'=>'#ff6600'],
            ['name'=>'J&T','color'=>'#d32f2f'],
            ['name'=>'SPX','color'=>'#EE4D2D'],
            ['name'=>'Viettel Post','color'=>'#e21a1a'],
            ['name'=>'Ninja Van','color'=>'#c41230'],
            ['name'=>'BEST','color'=>'#ffc107'],
            ['name'=>'Ahamove','color'=>'#f5a623'],
            ['name'=>'Grab Express','color'=>'#00b14f'],
            ['name'=>'Be','color'=>'#5bc500'],
            ['name'=>'Gojek','color'=>'#00aa13'],
        ],
        'subscription_plans' => [
            ['id'=>1,'name'=>'Miễn phí','price'=>0,'badge'=>''],
            ['id'=>2,'name'=>'Shipper Pro','price'=>49000,'badge'=>'⭐ PRO'],
            ['id'=>3,'name'=>'Shipper VIP','price'=>99000,'badge'=>'👑 VIP'],
            ['id'=>4,'name'=>'Shipper Premium','price'=>199000,'badge'=>'💎 PREMIUM'],
        ],
        'limits' => [
            'max_post_length' => 5000,
            'max_images_per_post' => 10,
            'max_file_size_mb' => 5,
            'story_duration_hours' => 24,
            'max_pins' => 3,
        ],
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
