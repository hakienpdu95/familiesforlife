<?php

namespace App\Shared\Traceability\Jobs;

use App\Shared\Traceability\TraceabilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshQrSnapshotJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly string $modelClass,
        public readonly int|string $modelId,
    ) {}

    public static function queueFor(string $modelClass, int|string|null $modelId): void
    {
        if ($modelId === null) {
            return;
        }

        static::dispatch($modelClass, $modelId)->afterCommit()->delay(now()->addSeconds(5));
    }

    public function uniqueId(): string
    {
        return $this->modelClass.':'.$this->modelId;
    }

    public function handle(TraceabilityService $traceability): void
    {
        $model = new ($this->modelClass);
        $model->setAttribute($model->getKeyName(), $this->modelId);
        $model->exists = true;

        $traceability->captureSnapshot($model);
    }
}
