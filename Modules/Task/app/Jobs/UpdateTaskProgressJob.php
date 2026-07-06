<?php

namespace Modules\Task\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Task\Models\Task;

class UpdateTaskProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $parentId) {}

    public function middleware(): array
    {
        return [new WithoutOverlapping("task_progress_{$this->parentId}", 30)];
    }

    public function handle(): void
    {
        $parent = Task::find($this->parentId);
        if (!$parent) {
            return;
        }

        $stats = DB::table('tasks')
            ->where('parent_id', $this->parentId)
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done
            ")
            ->first();

        $total = (int) $stats->total;
        $pct   = ($stats->active > 0)
            ? (int) round($stats->done / $stats->active * 100)
            : 0;

        DB::table('tasks')->where('id', $this->parentId)->update([
            'progress_pct' => $pct,
            'subtask_done' => (int) $stats->done,
            'is_leaf'      => $total === 0,
        ]);

        if ($parent->parent_id) {
            self::dispatch($parent->parent_id);
        }
    }
}
