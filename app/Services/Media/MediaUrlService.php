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
            return rtrim($cdnUrl, '/').'/'.$key;
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

        return $dir.'/'.$conversion.'.webp';
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

            $media = Media::query()->where('uuid', $uuid[1])->first();

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

    /** @param  array<string, int>  $widths  conversion => chiều rộng */
    public function srcset(Media $media, array $widths): ?string
    {
        $generated = $media->generated_conversions ?? [];

        $parts = collect($widths)
            ->filter(fn (int $width, string $conversion) => ! empty($generated[$conversion]))
            ->map(fn (int $width, string $conversion) => $this->url($media, $conversion).' '.$width.'w');

        return $parts->count() > 1 ? $parts->implode(', ') : null;
    }

    /**
     * Ảnh nhúng `<img data-media-uuid>` → src bản lớn nhất trong $widths đã có, thêm srcset/sizes
     * và loading="lazy" để trình duyệt di động chỉ tải bản vừa khung hình.
     *
     * @param  array<string, int>  $widths
     */
    public function responsiveEmbeddedImages(?string $html, array $widths, string $sizes): ?string
    {
        if (blank($html) || ! preg_match_all('/data-media-uuid="([^"]+)"/', $html, $found)) {
            return $html;
        }

        $media = Media::query()->whereIn('uuid', array_unique($found[1]))->get()->keyBy('uuid');

        return preg_replace_callback('/<img\b[^>]*>/i', function (array $tag) use ($media, $widths, $sizes) {
            $img = $tag[0];

            if (! preg_match('/data-media-uuid="([^"]+)"/', $img, $uuid) || ! ($item = $media->get($uuid[1])) || $item->mime_type === 'image/gif') {
                return $img;
            }

            $extra = '';

            if (! preg_match('/\ssrcset=/i', $img) && ($srcset = $this->srcset($item, $widths))) {
                $largest = collect($widths)->keys()->reverse()->first(fn ($c) => ! empty(($item->generated_conversions ?? [])[$c]));
                $img = preg_replace('/\ssrc="[^"]*"/', ' src="'.e($this->url($item, $largest)).'"', $img, 1);
                $extra .= ' srcset="'.e($srcset).'" sizes="'.e($sizes).'"';
            }
            if (! preg_match('/\sloading=/i', $img)) {
                $extra .= ' loading="lazy"';
            }
            if (! preg_match('/\sdecoding=/i', $img)) {
                $extra .= ' decoding="async"';
            }

            return $extra === '' ? $img : preg_replace('/^<img\b/i', '<img'.$extra, $img);
        }, $html);
    }

    private function isPublic(Media $media): bool
    {
        return (bool) ($media->custom_properties['is_public'] ?? true);
    }
}
