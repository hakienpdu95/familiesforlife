<?php

namespace App\Console\Commands;

use App\Shared\Traceability\Models\QrIdentity;
use App\Shared\Traceability\TraceabilityRegistry;
use App\Shared\Traceability\TraceabilityService;
use Illuminate\Console\Command;

class RefreshQrSnapshotsCommand extends Command
{
    protected $signature = 'qr:refresh-snapshots {--reset-unpublished : Xoá snapshot của đối tượng còn tồn tại nhưng đang không công khai}';

    protected $description = 'Chụp lại snapshot QR cho mọi đối tượng đang công khai';

    public function handle(TraceabilityRegistry $registry, TraceabilityService $traceability): int
    {
        $refreshed = 0;
        $reset = 0;

        foreach ($registry->all() as $modelClass => $presenterClass) {
            $presenter = app($presenterClass);
            $morph = (new $modelClass)->getMorphClass();

            QrIdentity::query()->where('target_type', $morph)->pluck('target_id')->unique()->chunk(200)->each(function ($ids) use ($modelClass, $presenter, $traceability, $morph, &$refreshed, &$reset) {
                $query = $modelClass::query();
                $presenter->constrain($query);

                foreach ($query->whereKey($ids->all())->get() as $model) {
                    if (! $model->trashed() && $presenter->isPublic($model)) {
                        $traceability->captureSnapshot($model);
                        $refreshed++;
                    } elseif (! $model->trashed() && $this->option('reset-unpublished')) {
                        $reset += QrIdentity::query()->where('target_type', $morph)->where('target_id', $model->getKey())
                            ->update(['snapshot' => null, 'snapshot_at' => null]);
                    }
                }
            });
        }

        $this->info("Đã chụp lại {$refreshed} snapshot, xoá {$reset} snapshot của đối tượng chưa công khai.");

        return self::SUCCESS;
    }
}
