<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Data\Requests\StoreTimeLogData;
use Modules\Task\Models\Task;
use Modules\Task\Models\TimeLog;

class StoreTimeLogAction
{
    use AsAction;

    public function handle(StoreTimeLogData $data): TimeLog
    {
        $task = Task::findOrFail($data->task_id);

        return TimeLog::create([
            'uuid'            => (string) Str::uuid(),
            'organization_id' => auth()->user()->organization_id,
            'task_id'         => $data->task_id,
            'project_id'      => $task->project_id,
            'employee_id'     => $data->employee_id,
            'hours'           => $data->hours,
            'log_date'        => $data->log_date,
            'description'     => $data->description,
            'is_billable'     => $data->is_billable,
        ]);
    }
}
