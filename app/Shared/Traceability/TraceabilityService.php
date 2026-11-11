<?php

namespace App\Shared\Traceability;

use App\Models\Media;
use App\Services\Media\MediaUrlService;
use App\Shared\Traceability\Contracts\ExposesPublicDocuments;
use App\Shared\Traceability\Models\QrIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class TraceabilityService
{
    public const ARCHIVE_DIR = 'archived_snapshots';

    public const RESPONSIVE_WIDTHS = ['small' => 480, 'medium' => 800];

    public const RESPONSIVE_SIZES = '(min-width: 576px) 576px, 100vw';

    public function __construct(
        private readonly TraceabilityRegistry $registry,
    ) {}

    public function resolveQrCode(string $qrCode): QrIdentity
    {
        $identity = QrIdentity::query()->where('qr_code', $qrCode)->firstOrFail();

        $constraints = [];
        foreach ($this->registry->all() as $modelClass => $presenterClass) {
            $constraints[$modelClass] = fn ($query) => app($presenterClass)->constrain($query);
        }

        if ($this->registry->presenterForMorphType($identity->target_type)) {
            $identity->load(['target' => fn (MorphTo $morph) => $morph->constrain($constraints)]);
        } else {
            $identity->setRelation('target', null);
        }

        return $identity;
    }

    public function present(QrIdentity $identity): array
    {
        $presenter = $this->registry->presenterForMorphType($identity->target_type);
        $target = $identity->target;
        $live = $target && ! (method_exists($target, 'trashed') && $target->trashed());
        $archived = false;

        if ($presenter && $live && $presenter->isPublic($target)) {
            $page = $presenter->page($presenter->snapshot($target), $target);
        } elseif ($presenter && $identity->snapshot) {
            $page = $presenter->page($identity->snapshot, null);
            $archived = true;
        } elseif ($presenter && $live) {
            $page = $presenter->pending($target);
        } else {
            $page = self::page(['title' => __('Không còn khả dụng'), 'notice' => 'Không còn dữ liệu lưu trữ cho mã QR này.']);
            $archived = true;
        }

        $status = ! $identity->isActive() ? 'revoked' : ($archived ? 'archived' : 'valid');

        $page = $this->responsiveHtml($page);

        return [
            ...$page,
            'view' => $presenter?->view() ?? 'traceability.show',
            'qr_code' => $identity->qr_code,
            'status' => $status,
            'revoked' => $status === 'revoked',
            'revoked_at' => format_date($identity->revoked_at),
            'archived' => $archived,
            'archived_at' => $archived ? format_date($identity->snapshot_at) : null,
            'issued_at' => format_date($identity->created_at),
            'data_updated_at' => format_date($archived ? $identity->snapshot_at : ($live ? $target->updated_at : null)),
            'official_host' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost(),
        ];
    }

    public const DOCUMENT_URL_MINUTES = 30;

    public const DOCUMENT_MIMES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    public function publicDocument(QrIdentity $identity, string $uuid): ?Media
    {
        $presenter = $this->registry->presenterForMorphType($identity->target_type);
        $target = $identity->target;

        if (! $identity->isActive() || ! $presenter instanceof ExposesPublicDocuments || ! $target
            || (method_exists($target, 'trashed') && $target->trashed()) || ! $presenter->isPublic($target)) {
            return null;
        }

        return Media::query()
            ->where('uuid', $uuid)
            ->whereIn('model_type', array_unique([$target->getMorphClass(), $target::class]))
            ->where('model_id', $target->getKey())
            ->whereIn('collection_name', $presenter->publicDocumentCollections())
            ->whereIn('mime_type', self::DOCUMENT_MIMES)
            ->first();
    }

    public static function documentUrl(string $qrCode, string $uuid): string
    {
        return URL::temporarySignedRoute('traceability.document', now()->addMinutes(self::DOCUMENT_URL_MINUTES), ['qr_code' => $qrCode, 'media' => $uuid]);
    }

    private function responsiveHtml(array $page): array
    {
        $urls = app(MediaUrlService::class);
        $apply = fn (?string $html) => $urls->responsiveEmbeddedImages($html, self::RESPONSIVE_WIDTHS, self::RESPONSIVE_SIZES);

        if (filled($page['content_html'] ?? null)) {
            $page['content_html'] = $apply($page['content_html']);
        }

        foreach ($page['sections'] ?? [] as $i => $section) {
            if (filled($section['html'] ?? null)) {
                $page['sections'][$i]['html'] = $apply($section['html']);
            }
        }

        return $page;
    }

    public function captureSnapshot(Model $model): void
    {
        $presenter = $this->registry->presenterForClass($model::class);

        if (! $presenter || ! method_exists($model, 'qrIdentities')) {
            return;
        }

        $query = $model->newQuery()->whereKey($model->getKey());
        $presenter->constrain($query);
        $fresh = $query->first();

        if (! $fresh || (method_exists($fresh, 'trashed') && $fresh->trashed()) || ! $presenter->isPublic($fresh)) {
            return;
        }

        $payload = $presenter->snapshot($fresh);

        $model->qrIdentities()->get()->each(fn (QrIdentity $identity) => $identity->storeSnapshot($payload));
    }

    public function archiveSnapshotMedia(Model $model): void
    {
        if (! method_exists($model, 'qrIdentities')) {
            return;
        }

        $public = Storage::disk('public');
        $folder = self::ARCHIVE_DIR.'/'.$model->getMorphClass().'/'.$model->getKey();

        foreach ($model->qrIdentities()->get() as $identity) {
            $snapshot = $identity->snapshot ?? [];
            $image = $snapshot['image'] ?? null;

            if (! $image || ! empty($image['archived']) || empty($image['path']) || empty($image['disk'])) {
                continue;
            }

            $destination = $folder.'/'.basename($image['path']);

            if (! $public->exists($destination)) {
                $source = Storage::disk($image['disk']);

                if (! $source->exists($image['path'])) {
                    continue;
                }

                $public->writeStream($destination, $source->readStream($image['path']));
                $source->delete($image['path']);
            }

            $snapshot['image'] = ['url' => $public->url($destination), 'disk' => 'public', 'path' => $destination, 'archived' => true];
            $identity->forceFill(['snapshot' => $snapshot])->save();
        }
    }

    public static function imageRef(Model $model, string $collection, string $conversion = ''): ?array
    {
        $media = method_exists($model, 'getFirstMedia') ? $model->getFirstMedia($collection) : null;
        $url = $media ? $model->getFirstMediaUrl($collection, $conversion) : '';

        if (! $media || $url === '') {
            return null;
        }

        $diskUrl = rtrim(Storage::disk($media->disk)->url(''), '/').'/';
        $path = str_starts_with($url, $diskUrl) ? substr($url, strlen($diskUrl)) : null;

        return [
            'url' => $url,
            'media_uuid' => $media->uuid,
            'disk' => $media->disk,
            'path' => $path ? rawurldecode(strtok($path, '?')) : null,
        ];
    }

    public static function page(array $page): array
    {
        return [
            'heading' => __('Thông tin truy xuất nguồn gốc'),
            'icon' => 'fa-qrcode',
            'kind' => 'Đối tượng',
            'title' => __('Không xác định'),
            'subtitle' => null,
            'images' => [],
            'image_srcsets' => [],
            'badges' => [],
            'facts' => [],
            'notice' => null,
            'sections' => [],
            'related' => [],
            'cta' => null,
            'cta_secondary' => null,
            'cta_sticky' => true,
            ...$page,
        ];
    }

    public static function srcset(Media $media): ?string
    {
        return app(MediaUrlService::class)->srcset($media, self::RESPONSIVE_WIDTHS);
    }

    public static function tidyHtml(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $block = '(?:p|ul|ol|h[1-6]|blockquote|div|table|figure)';

        return trim(preg_replace([
            '#<p\b[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</p>#iu',
            '#(</'.$block.'>)(?:\s|<br\s*/?>)+#iu',
            '#(?:<br\s*/?>\s*)+(<'.$block.'\b)#iu',
            '#(<p\b[^>]*>)\s*(?:<br\s*/?>\s*)+#iu',
            '#(?:\s*<br\s*/?>)+\s*(</p>)#iu',
        ], ['', '$1', '$1', '$1', '$1'], $html));
    }

    public static function hasContent(?string $html): bool
    {
        $text = strip_tags((string) $html, '<img><iframe><video>');

        return trim(str_replace(["\u{00A0}", '&nbsp;'], ' ', $text)) !== '';
    }

    public static function fact(string $icon, string $label, ?string $value): ?array
    {
        return filled($value) ? ['icon' => $icon, 'label' => $label, 'value' => $value] : null;
    }
}
