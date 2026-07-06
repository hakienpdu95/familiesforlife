<?php

namespace Modules\Task\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Task\Data\Requests\StoreTaskData;
use Modules\Task\Events\TaskCreated;
use Modules\Task\Models\Task;

class StoreTaskAction
{
    use AsAction;

    public function handle(StoreTaskData $data): Task
    {
        $depth    = 0;
        $parentId = $data->parent_id;

        if ($parentId) {
            $parent = Task::findOrFail($parentId);
            abort_if($parent->depth >= 3, 422, 'Không thể tạo công việc quá 3 cấp sâu.');
            abort_if($parent->project_id !== $data->project_id, 422, 'Công việc con phải cùng dự án với công việc cha.');
            $depth = $parent->depth + 1;
        }

        $sortOrder = (int) DB::table('tasks')
            ->where('project_id', $data->project_id)
            ->where('parent_id', $parentId)
            ->whereNull('deleted_at')
            ->max('sort_order') + 1;

        $task = Task::create([
            'uuid'             => (string) Str::uuid(),
            'organization_id'  => $data->organization_id ?? auth()->user()->organization_id ?? TenantContext::getOrganizationId(),
            'project_id'       => $data->project_id,
            'parent_id'        => $parentId,
            'employee_id'      => $data->employee_id,
            'title'            => $data->title,
            'description'      => $data->description,
            'task_type'        => $data->task_type->value,
            'status'           => $data->status->value,
            'priority'         => $data->priority->value,
            'story_points'     => $data->story_points,
            'start_date'       => $data->start_date,
            'due_date'         => $data->due_date,
            'estimated_hours'  => $data->estimated_hours,
            'sort_order'       => $sortOrder,
            'depth'            => $depth,
            'created_by'       => auth()->id(),
            'updated_by'       => auth()->id(),
        ]);

        if ($parentId) {
            DB::table('tasks')->where('id', $parentId)->update(['is_leaf' => false]);
            DB::table('tasks')->where('id', $parentId)->increment('subtask_total');
        }

        if (!empty($data->label_ids)) {
            $task->labels()->sync($data->label_ids);
        }

        // Audit trail: log creation
        DB::table('task_histories')->insert([
            'task_id'       => $task->id,
            'actor_id'      => auth()->id(),
            'field_changed' => 'created',
            'old_value'     => null,
            'new_value'     => $task->uuid,
            'changed_at'    => now(),
        ]);

        event(new TaskCreated($task));

        return $task;
    }
}
