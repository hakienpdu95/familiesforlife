<?php

namespace Modules\Task\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Data\Requests\UpdateTimeLogData;
use Modules\Task\Models\TimeLog;

class UpdateTimeLogAction
{
    use AsAction;

    public function handle(TimeLog $log, UpdateTimeLogData $data): TimeLog
    {
        $updates = collect([
            'hours'       => $data->hours,
            'log_date'    => $data->log_date,
            'is_billable' => $data->is_billable,
        ])->reject(fn ($v) => $v === null)->toArray();

        // description can be explicitly cleared to null
        if (array_key_exists('description', request()->all())) {
            $updates['description'] = $data->description;
        }

        $log->update($updates);

        return $log->fresh();
    }
}
