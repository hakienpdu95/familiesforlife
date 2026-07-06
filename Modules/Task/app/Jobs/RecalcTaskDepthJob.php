<?php

namespace Modules\Task\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RecalcTaskDepthJob implements ShouldQueue
{
    use AsAction, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly int $taskId) {}

    public function handle(): void
    {
        // BFS — tái tính depth cho toàn bộ descendants (max 3 iterations)
        $queue = [$this->taskId];

        for ($iteration = 0; $iteration < 4 && ! empty($queue); $iteration++) {
            $nextQueue = [];

            foreach ($queue as $parentId) {
                $parent = DB::table('tasks')->where('id', $parentId)->value('depth');
                if ($parent === null) continue;

                $children = DB::table('tasks')
                    ->where('parent_id', $parentId)
                    ->whereNull('deleted_at')
                    ->pluck('id');

                foreach ($children as $childId) {
                    DB::table('tasks')->where('id', $childId)->update(['depth' => $parent + 1]);
                    $nextQueue[] = $childId;
                }
            }

            $queue = $nextQueue;
        }
    }
}
