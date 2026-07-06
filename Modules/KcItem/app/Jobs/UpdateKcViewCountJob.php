<?php

namespace Modules\KcItem\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\KcItem\Models\KcItem;
use Modules\KcItem\Models\KcViewLog;

class UpdateKcViewCountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly int $kcItemId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $count = KcViewLog::where('item_id', $this->kcItemId)->count();

        KcItem::withoutTenant()
            ->where('id', $this->kcItemId)
            ->update(['view_count' => $count]);
    }
}
