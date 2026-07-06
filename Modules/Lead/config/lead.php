<?php

return [
    'queue'                     => env('LEAD_QUEUE', 'default'),
    'stale_days'                => env('LEAD_STALE_DAYS', 14),
    'hot_score_threshold'       => env('LEAD_HOT_SCORE', 70),
    'default_currency'          => env('LEAD_DEFAULT_CURRENCY', 'VND'),
    'stage_history_retain_days' => env('LEAD_STAGE_HISTORY_DAYS', 365),
    'default_assignee_id'       => env('LEAD_DEFAULT_ASSIGNEE', null),
    'default_organization_id'   => env('LEAD_DEFAULT_ORG_ID', 1),

    'cache_ttl' => [
        'pipeline_stages' => 600,
        'lead_sources'    => 600,
        'kanban'          => 60,
        'stats'           => 300,
        'score'           => 3600,
    ],
];
