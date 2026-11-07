<?php

namespace App\Shared\Traceability\Traits;

use App\Shared\Traceability\Jobs\RefreshQrSnapshotJob;
use App\Shared\Traceability\Models\QrIdentity;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Snapshot chỉ chụp khi đối tượng đang công khai: sau mỗi lần lưu (job xếp hàng, gộp trùng) và
 * đồng bộ ngay trước khi xoá (mềm hoặc cứng). Xoá cứng: ảnh trong snapshot được chuyển sang
 * storage/app/public/archived_snapshots trước khi media library xoá file gốc.
 */
trait HasQrIdentity
{
    public static function bootHasQrIdentity(): void
    {
        static::created(function (self $model): void {
            QrIdentity::issueFor($model);
        });

        static::saved(function (self $model): void {
            RefreshQrSnapshotJob::queueFor($model::class, $model->getKey());
        });

        static::deleting(function (self $model): void {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                return;
            }

            app(TraceabilityService::class)->captureSnapshot($model);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::forceDeleting(function (self $model): void {
                $traceability = app(TraceabilityService::class);
                $traceability->captureSnapshot($model);
                $traceability->archiveSnapshotMedia($model);
            });
        }
    }

    public function qrIdentities(): MorphMany
    {
        return $this->morphMany(QrIdentity::class, 'target');
    }

    public function qrIdentity(): MorphOne
    {
        return $this->morphOne(QrIdentity::class, 'target')
            ->ofMany(['id' => 'min'], fn ($query) => $query->where('status', QrIdentity::STATUS_ACTIVE));
    }
}
