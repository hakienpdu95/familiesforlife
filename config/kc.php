<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attachment limits
    |--------------------------------------------------------------------------
    | Storage config removed — now handled by config/media.php (collection: attachments).
    */
    'attachments' => [
        'max_file_size_mb'  => (int) env('KC_MAX_FILE_SIZE_MB', 50),
        'max_item_total_mb' => (int) env('KC_MAX_ITEM_TOTAL_MB', 200),

        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'png', 'jpg', 'jpeg', 'gif', 'webp',
            'mp4', 'webm',
            'zip',
        ],
    ],

];
