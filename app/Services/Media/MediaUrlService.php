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

    /**
     * Cập nhật lại `src` của mọi `<img data-media-uuid="...">` trong HTML theo vị trí HIỆN TẠI
     * của media — sau reassociateOrphans() file đã chuyển từ thư mục JoditDraft sang thư mục
     * của entity thật, URL nhúng sẵn trong nội dung lúc soạn trỏ tới path cũ không còn tồn tại.
     * Giữ nguyên conversion đang dùng (VD `medium.webp` → vẫn là medium).
     */
    public function refreshEmbeddedImageUrls(?string $html): ?string
    {
        if (blank($html) || ! str_contains($html, 'data-media-uuid')) {
            return $html;
        }

        return preg_replace_callback('/<img\b[^>]*>/i', function (array $tag) {
            $img = $tag[0];

            if (! preg_match('/data-media-uuid="([^"]+)"/', $img, $uuid)
                || ! preg_match('/\ssrc="([^"]*)"/', $img, $src)) {
                return $img;
            }

            $media = Media::withoutTenant()->where('uuid', $uuid[1])->first();

            if (! $media) {
                return $img;
            }

            $conversion = preg_match('#/([a-z]+)\.webp(?:\?[^"]*)?$#', $src[1], $conv)
                && array_key_exists($conv[1], config('media.conversion_settings', []))
                ? $conv[1]
                : '';

            return str_replace($src[0], ' src="'.e($this->url($media, $conversion)).'"', $img);
        }, $html);
    }

    private function isPublic(Media $media): bool
    {
        return (bool) ($media->custom_properties['is_public'] ?? true);
    }
}
