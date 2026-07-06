<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Data\Requests\UpdateTaskData;
use Modules\Task\Events\TaskUpdated;
use Modules\Task\Models\Task;
use Modules\Task\Models\TaskLabelHistory;

class UpdateTaskAction
{
    use AsAction;

    /** Scalar fields tracked in task_histories */
    private const TRACKED = ['title', 'status', 'priority', 'employee_id', 'due_date'];

    public function handle(Task $task, UpdateTaskData $data): Task
    {
        $actorId    = auth()->id();
        $wasCompleted = $task->status->isDone();
        $willComplete = $data->status->isDone();

        $newValues = [
            'employee_id'     => $data->employee_id,
            'title'           => $data->title,
            'description'     => $data->description,
            'task_type'       => $data->task_type->value,
            'status'          => $data->status->value,
            'priority'        => $data->priority->value,
            'story_points'    => $data->story_points,
            'start_date'      => $data->start_date,
            'due_date'        => $data->due_date,
            'estimated_hours' => $data->estimated_hours,
            'updated_by'      => $actorId,
        ];

        // Collect history records before mutating
        $historyRows = [];
        $now = now();
        foreach (self::TRACKED as $field) {
            $oldVal = (string) ($task->{$field} instanceof \BackedEnum
                ? $task->{$field}->value
                : ($task->{$field} ?? ''));
            $newVal = (string) ($newValues[$field] ?? '');

            if ($oldVal !== $newVal) {
                // description changes only log 'updated' — no full content
                $historyRows[] = [
                    'task_id'       => $task->id,
                    'actor_id'      => $actorId,
                    'field_changed' => $field,
                    'old_value'     => $oldVal,
                    'new_value'     => $field === 'description' ? 'updated' : $newVal,
                    'changed_at'    => $now,
                ];
            }
        }

        $task->fill($newValues);

        if ($willComplete && !$wasCompleted) {
            $task->completed_at = now();
        } elseif (!$willComplete && $wasCompleted) {
            $task->completed_at = null;
        }

        $task->save();

        // Sync labels and write label history
        $oldLabelIds = $task->labels()->pluck('task_labels.id')->toArray();
        $newLabelIds = $data->label_ids ?? [];

        $task->labels()->sync($newLabelIds);

        $added   = array_diff($newLabelIds, $oldLabelIds);
        $removed = array_diff($oldLabelIds, $newLabelIds);

        $labelHistoryRows = [];
        foreach ($added as $lid) {
            $labelHistoryRows[] = ['task_id' => $task->id, 'label_id' => $lid, 'actor_id' => $actorId, 'action' => 'added', 'changed_at' => $now];
        }
        foreach ($removed as $lid) {
            $labelHistoryRows[] = ['task_id' => $task->id, 'label_id' => $lid, 'actor_id' => $actorId, 'action' => 'removed', 'changed_at' => $now];
        }

        if (! empty($historyRows)) {
            DB::table('task_histories')->insert($historyRows);
        }
        if (! empty($labelHistoryRows)) {
            DB::table('task_label_histories')->insert($labelHistoryRows);
        }

        event(new TaskUpdated($task));

        return $task;
    }
}
