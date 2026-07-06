<?php

return [
    /*
     * The model used for media records.
     * Custom model extends Spatie's Media with BelongsToOrganization for tenant isolation.
     * NOTE: Must NOT extend TenantAwareModel — Spatie does not support SoftDeletes.
     */
    'media_model' => App\Models\Media::class,

    /*
     * When enabled, Spatie will perform media conversions using a queued job.
     * We handle conversions synchronously in MediaUploadService — keep this false.
     */
    'queue_conversions_by_default' => false,

    'disk_name' => env('MEDIA_DISK', 'public'),

    'max_file_size' => 1024 * 1024 * 50, // 50 MB global cap

    'temporary_upload_expiration_time_in_minutes' => 30,

    'jobs' => [
        'perform_conversions' => Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob::class,
        'generate_responsive_images' => Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImagesJob::class,
    ],

    'image_generators' => [
        Spatie\MediaLibrary\Conversions\ImageGenerators\Image::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Webp::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Pdf::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Svg::class,
        Spatie\MediaLibrary\Conversions\ImageGenerators\Video::class,
    ],

    'path_generator' => App\Services\Media\MediaPathGenerator::class,

    'url_generator' => Spatie\MediaLibrary\Support\UrlGenerator\DefaultUrlGenerator::class,

    'moves_media_on_update' => false,

    'version_urls' => false,

    'image_optimizers' => [],

    'image_driver' => env('IMAGE_DRIVER', 'gd'),

    'remote' => [
        'extra_headers' => [
            'CacheControl' => 'max-age=604800',
        ],
    ],

    'responsive_images' => [
        'use_tiny_placeholders' => true,
        'tiny_placeholder_generator' => Spatie\MediaLibrary\ResponsiveImages\TinyPlaceholderGenerator\Blurha::class,
    ],

    'previewable_mimes' => [
        'image/jpeg', 'image/gif', 'image/png', 'image/svg+xml', 'image/webp',
        'application/pdf',
    ],
];
