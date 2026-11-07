<?php

namespace App\Shared\Traceability;

use App\Shared\Traceability\Models\QrIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class TraceabilityService
{
    public const ARCHIVE_DIR = 'archived_snapshots';

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
            $page = self::page(['title' => 'Không còn khả dụng', 'notice' => 'Không còn dữ liệu lưu trữ cho mã QR này.']);
            $archived = true;
        }

        return [
            ...$page,
            'view' => $presenter?->view() ?? 'traceability.show',
            'qr_code' => $identity->qr_code,
            'revoked' => ! $identity->isActive(),
            'archived' => $archived,
            'archived_at' => $archived ? $identity->snapshot_at?->format('d/m/Y') : null,
        ];
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
            'heading' => 'Thông tin truy xuất nguồn gốc',
            'icon' => 'fa-qrcode',
            'title' => 'Sản phẩm',
            'image' => null,
            'facts' => [],
            'notice' => null,
            'sections' => [],
            'cta' => ['url' => route('post.public.home'), 'label' => 'Về trang chủ'],
            'archive_text' => 'Cảnh báo: Thông tin dưới đây là bản lưu trữ. Đối tượng này hiện đã bị gỡ khỏi hệ thống.',
            ...$page,
        ];
    }
}
