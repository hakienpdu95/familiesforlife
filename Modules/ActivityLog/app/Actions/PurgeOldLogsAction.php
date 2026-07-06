<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;

class PurgeOldLogsAction
{
    use AsAction;

    public string $commandSignature   = 'activitylog:purge';
    public string $commandDescription = 'Xóa log cũ theo retention policy';

    public function handle(): void
    {
        $cutoff    = now()->subDays(config('activitylog_module.retain_days', 90));
        $batchSize = 1000;
        $deleted   = 0;

        do {
            $ids = DB::table('activity_log')
                ->where('created_at', '<', $cutoff)
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) break;

            DB::table('activity_log_contexts')->whereIn('log_id', $ids)->delete();
            DB::table('activity_log_http')->whereIn('log_id', $ids)->delete();
            $deleted += DB::table('activity_log')->whereIn('id', $ids)->delete();

            usleep(10_000);
        } while ($ids->count() === $batchSize);

        if ($deleted > 0) {
            ActivityLogger::info('ActivityLog', 'logs_purged', null, [
                'deleted_count' => $deleted,
                'cutoff_date'   => $cutoff->toDateString(),
            ]);
        }
    }

    public function asCommand(\Illuminate\Console\Command $command): void
    {
        $this->handle();
        $command->info('ActivityLog: purge hoàn tất.');
    }
}
