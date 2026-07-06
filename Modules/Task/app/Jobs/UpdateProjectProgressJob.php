<?php

namespace Modules\Task\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateProjectProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $projectId) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping("proj_progress_{$this->projectId}", 30)];
    }

    public function handle(): void
    {
        $stats = DB::table('tasks')
            ->where('project_id', $this->projectId)
            ->where('is_leaf', true)
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done
            ")
            ->first();

        $pct = ($stats->active > 0)
            ? (int) round($stats->done / $stats->active * 100)
            : 0;

        DB::table('projects')->where('id', $this->projectId)->update([
            'progress_pct' => $pct,
            'task_total'   => (int) $stats->total,
            'task_done'    => (int) $stats->done,
        ]);
    }
}
