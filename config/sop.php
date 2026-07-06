<?php

return [
    'attachments' => [
        // Storage keys removed — now handled by config/media.php (collection: attachments).
        // Kept: allowed_extensions for controller-level validation (human-readable format).
        'allowed_extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
            'png', 'jpg', 'jpeg', 'gif', 'webp',
            'txt', 'zip',
        ],
    ],
];
