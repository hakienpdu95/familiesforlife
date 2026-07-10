<?php

// Cấu hình provider AI mặc định của nền tảng — dùng khi Organization chưa tự mang
// API key riêng (BYOK, xem organizations.ai_provider_config).
return [
    'default' => [
        'provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
        'model'    => env('AI_DEFAULT_MODEL', 'claude-sonnet-5'),
        'api_key'  => env('AI_DEFAULT_API_KEY'),
    ],
];
