<?php

return [

    'events' => [
        'commerce' => 'affiliate_click',
        'contact' => 'contact_click',
        'outbound' => 'outbound_click',
    ],

    'platforms' => [
        'shopee' => ['shopee.vn', 'shopee.com', 'shp.ee'],
        'tiktok' => ['tiktok.com', 'tiktokshop.com'],
        'lazada' => ['lazada.vn', 'lazada.com'],
        'tiki' => ['tiki.vn'],
        'sendo' => ['sendo.vn'],
        'zalo' => ['zalo.me', 'zalo.vn'],
        'messenger' => ['m.me', 'messenger.com'],
        'facebook' => ['facebook.com', 'fb.com', 'fb.me', 'fb.watch'],
        'youtube' => ['youtube.com', 'youtu.be'],
        'instagram' => ['instagram.com'],
        'google_maps' => ['maps.google.com', 'maps.app.goo.gl'],
    ],

    'commerce_platforms' => ['shopee', 'tiktok', 'lazada', 'tiki', 'sendo', 'supplier'],

    'contact_platforms' => ['zalo', 'messenger'],

];
