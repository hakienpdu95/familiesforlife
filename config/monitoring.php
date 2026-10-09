<?php

return [

    'enabled' => env('MONITORING_ENABLED', true),

    'redis_connection' => env('MONITORING_REDIS_CONNECTION', 'default'),

    'slow_request_ms' => (int) env('MONITORING_SLOW_REQUEST_MS', 1000),

    'slow_query_ms' => (int) env('MONITORING_SLOW_QUERY_MS', 500),

    'retention_hours' => 26,

    'slow_list_size' => 50,

    'ignore_paths' => [
        'horizon/*',
        'dashboard/system-monitor/data',
        'up',
    ],

];
