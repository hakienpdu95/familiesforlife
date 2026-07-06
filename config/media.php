<?php

return [
    /*
     * Default disk for new uploads.
     * Override per-collection via 'disk' key in collections config.
     */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
     * CDN base URL. When set, MediaUrlService prepends this to storage_key
     * instead of calling Storage::disk()->url().
     * Changing CDN domain = change this one env var, zero DB updates.
     */
    'cdn_url' => env('MEDIA_CDN_URL'),

    /*
     * TTL for Jodit orphan cleanup (hours from last_touched_at).
     * 72h accounts for long editing sessions and weekend work.
     */
    'jodit_orphan_ttl_hours' => 72,

    /*
     * TTL for FilePond draft cleanup (hours from media.created_at).
     * FilePond has no touch mechanism — any draft older than TTL was never submitted.
     */
    'filepond_orphan_ttl_hours' => 72,

    /*
     * Per-collection configuration.
     * is_public:    true  → static URL via disk/CDN
     *               false → temporaryUrl (presigned, 30 min)
     * disk:         override global disk for this collection
     * conversions:  which variants to generate (must match conversion_settings keys)
     * max_size_kb:  per-upload size limit
     * allowed_mime: allowlist; ['*'] = any MIME
     */
    'collections' => [
        'avatar' => [
            'max_size_kb'  => 5120,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium'],
        ],
        'logo' => [
            'max_size_kb'  => 5120,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium'],
        ],
        'thumbnail' => [
            'max_size_kb'  => 5120,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium'],
        ],
        'cover' => [
            'max_size_kb'  => 10240,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp'],
            'is_public'    => true,
            'conversions'  => ['thumb', 'medium', 'preview'],
        ],
        'attachments' => [
            'max_size_kb'  => 51200,
            'allowed_mime' => [
                // Documents
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-zip-compressed',
                // Images
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            ],
            'is_public'    => true,
            'conversions'  => [],
        ],
        'attachments_private' => [
            'max_size_kb'  => 51200,
            'allowed_mime' => [
                // Documents
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
                'application/zip',
                'application/x-zip-compressed',
                // Images
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            ],
            'is_public'    => false,
            'disk'         => 'local',
            'conversions'  => [],
        ],
        'jodit_content' => [
            'max_size_kb'  => 10240,
            'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'is_public'    => true,
            'conversions'  => ['medium'],
        ],
    ],

    /*
     * Conversion variant settings.
     * method: 'crop' (cover, fills dimensions) | 'scale' (resize preserving ratio)
     */
    'conversion_settings' => [
        'thumb' => [
            'width'   => 150,
            'height'  => 150,
            'method'  => 'crop',
            'quality' => 85,
        ],
        'medium' => [
            'width'   => 800,
            'height'  => null,
            'method'  => 'scale',
            'quality' => 82,
        ],
        'preview' => [
            'width'   => 1200,
            'height'  => null,
            'method'  => 'scale',
            'quality' => 80,
        ],
    ],
];
