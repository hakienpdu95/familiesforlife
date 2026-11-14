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

    'content_groups' => [
        'post.public.home' => 'home',
        'post.public.article' => 'article',
        'post.public.category' => 'article_listing',
        'post.public.author-hub.*' => 'author',
        'ocop.public.show' => 'ocop_product',
        'ocop.public.index' => 'ocop_listing',
        'ocop-subject.public.show' => 'brand',
        'event.public.show' => 'event',
        'event.public.submit.*' => 'event_submit',
        'event.public.*' => 'event_listing',
        'heritage.public.show' => 'heritage',
        'heritage.public.index' => 'heritage_listing',
        'province.public.*' => 'province',
        'playlist.public.*' => 'video',
        'video.index' => 'video',
        'real-estate.public.*' => 'real_estate',
        'page.public.*' => 'page',
        'newsletter.public.*' => 'newsletter',
        'traceability.*' => 'qr_trace',
    ],

];
