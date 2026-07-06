<?php
return [
    'queue' => env('CUSTOMER_QUEUE', 'default'),

    'dedup' => [
        'strategy' => 'email_then_phone',
    ],

    'conversion' => [
        'auto_convert_on_lead_won' => true,
    ],

    'pagination' => [
        'default_per_page' => 25,
    ],

    'cache_ttl' => [
        'tags'    => 600,
        'sources' => 600,
    ],
];
