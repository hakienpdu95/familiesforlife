<?php

namespace App\Services\Media;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Central URL resolver for all media files.
 *
 * Never hardcodes CDN domains — derives URL at runtime from:
 *   1. custom_properties.is_public = false → presigned temporaryUrl (30 min)
 *   2. MEDIA_CDN_URL configured           → cdn_url + storage_key
 *   3. fallback                            → Storage::disk()->url()
 *
 * To change CDN: update MEDIA_CDN_URL env var, zero DB updates needed.
 */
class MediaUrlService
{
    public function url(Media $media, string $conversion = ''): string
    {
        $key = $this->resolveKey($media, $conversion);

        // Private files always get presigned URLs regardless of CDN config
        if (! $this->isPublic($media)) {
            return $this->temporaryUrl($media, $conversion);
        }

        // External disk (backward compat for migrated URL columns — file_name stores the full URL)
        if ($media->disk === 'external') {
            return $media->file_name;
        }

        $cdnUrl = config('media.cdn_url');
        if ($cdnUrl) {
            return rtrim($cdnUrl, '/') . '/' . $key;
        }

        return Storage::disk($media->disk)->url($key);
    }

    public function temporaryUrl(Media $media, string $conversion = '', int $ttlMinutes = 30): string
    {
        $key = $this->resolveKey($media, $conversion);

        return Storage::disk($media->disk)->temporaryUrl(
            $key,
            now()->addMinutes($ttlMinutes)
        );
    }

    private function resolveKey(Media $media, string $conversion): string
    {
        if ($conversion === '') {
            return $media->getPathRelativeToRoot();
        }

        // If variant not yet generated, fall back to original
        $generated = $media->generated_conversions ?? [];
        if (empty($generated[$conversion])) {
            return $media->getPathRelativeToRoot();
        }

        // Conversions live in the same directory as the original
        $dir = rtrim(dirname($media->getPathRelativeToRoot()), '/');
        return $dir . '/' . $conversion . '.webp';
    }

    private function isPublic(Media $media): bool
    {
        return (bool) ($media->custom_properties['is_public'] ?? true);
    }
}
