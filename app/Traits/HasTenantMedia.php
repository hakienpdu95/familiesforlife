<?php

namespace App\Traits;

use App\Models\Media;
use App\Services\Media\MediaUrlService;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Drop-in trait for domain models that need media support.
 *
 * Usage: add `use HasTenantMedia;` to any model that needs file/image uploads.
 * Implements HasMedia interface so Spatie recognises the model as media-attachable.
 *
 * NOTE: The model class must ALSO implement HasMedia interface:
 *   class Employee extends TenantAwareModel implements HasMedia
 *   {
 *       use HasTenantMedia;
 *   }
 */
trait HasTenantMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        // Collections are configured in config/media.php.
        // No registration needed here — Spatie accepts any collection name.
    }

    /**
     * Get the URL for the first media item in a collection, optionally with a conversion.
     * Returns empty string if no media found (safe for Blade {{ }}).
     */
    public function getMediaUrl(string $collection, string $conversion = ''): string
    {
        /** @var Media|null $media */
        $media = $this->getFirstMedia($collection);

        if (! $media) {
            return '';
        }

        return app(MediaUrlService::class)->url($media, $conversion);
    }

    /**
     * Alias for getMediaUrl — matches Spatie's getFirstMediaUrl naming convention.
     */
    public function getFirstMediaUrl(string $collection = 'default', string $conversion = ''): string
    {
        return $this->getMediaUrl($collection, $conversion);
    }
}
