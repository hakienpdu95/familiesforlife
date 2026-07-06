<?php

namespace Modules\WorkflowAutomation\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;

class PurgeOldExecutionsAction
{
    use AsAction;

    public function handle(): void
    {
        $retainDays = (int) config('workflow_automation.retain_execution_days', 60);
        $cutoff     = now()->subDays($retainDays);

        $execIds = \DB::table('workflow_executions')
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        if ($execIds->isEmpty()) return;

        \DB::table('workflow_execution_steps')
            ->whereIn('execution_id', $execIds)
            ->delete();

        \DB::table('workflow_executions')
            ->whereIn('id', $execIds)
            ->delete();

        ActivityLogger::info('WorkflowAutomation', 'executions_purged', null, [
            'count'  => $execIds->count(),
            'cutoff' => $cutoff->toDateString(),
        ]);
    }
}
