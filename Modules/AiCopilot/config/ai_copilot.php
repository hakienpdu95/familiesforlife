<?php

return [

    'providers' => [
        'claude' => [
            'api_key'       => env('ANTHROPIC_API_KEY'),
            'default_model' => 'claude-haiku-4-5-20251001',
        ],
        'openai' => [
            'api_key'       => env('OPENAI_API_KEY'),
            'default_model' => 'gpt-4o-mini',
        ],
    ],

    'queue' => [
        'connection'  => env('AI_QUEUE_CONNECTION', 'database'),
        'name'        => 'ai',
        'retry_after' => 90,
    ],

    'defaults' => [
        'temperature'     => 0.7,
        'max_tokens'      => 1024,
        'timeout_seconds' => 30,
    ],

    'cost_alert_usd' => env('AI_COST_ALERT_USD', 50.0),

];
