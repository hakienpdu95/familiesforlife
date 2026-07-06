<?php

namespace Modules\Task\Observers;

use Illuminate\Support\Facades\DB;
use Modules\Task\Models\TimeLog;

class TimeLogObserver
{
    public function created(TimeLog $log): void
    {
        DB::table('tasks')->where('id', $log->task_id)
            ->increment('logged_hours', $log->hours);
    }

    public function updated(TimeLog $log): void
    {
        $diff = $log->hours - $log->getOriginal('hours');

        if ($diff == 0) {
            return;
        }

        if ($diff > 0) {
            DB::table('tasks')->where('id', $log->task_id)->increment('logged_hours', $diff);
        } else {
            DB::table('tasks')->where('id', $log->task_id)->decrement('logged_hours', abs($diff));
        }
    }

    public function deleted(TimeLog $log): void
    {
        DB::table('tasks')->where('id', $log->task_id)
            ->decrement('logged_hours', $log->hours);
    }
}
