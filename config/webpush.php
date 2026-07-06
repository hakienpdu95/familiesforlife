<?php

return [
    'vapid' => [
        'subject'     => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key'  => env('VAPID_PUBLIC_KEY', ''),
        'private_key' => env('VAPID_PRIVATE_KEY', ''),
    ],

    // Timeout (seconds) for push HTTP requests
    'timeout' => env('WEBPUSH_TIMEOUT', 30),

    // Max concurrent requests in a flush batch (Guzzle Pool)
    'batch_size' => env('WEBPUSH_BATCH_SIZE', 200),
];
