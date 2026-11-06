<?php
return [
    'queue' => env('CUSTOMER_QUEUE', 'default'),

    'dedup' => [
        'strategy' => 'email_then_phone',
    ],

    'pagination' => [
        'default_per_page' => 25,
    ],

    'cache_ttl' => [
        'tags'    => 600,
    ],
];
