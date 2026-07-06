<?php

namespace Modules\Deployment\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Task\Models\Task;

class CreatePhaseTaskAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, string $newPhase): ?Task
    {
        if (! $target->project_id) {
            return null;
        }

        $sortOrder = (int) DB::table('tasks')
            ->where('project_id', $target->project_id)
            ->whereNull('parent_id')
            ->whereNull('deleted_at')
            ->max('sort_order') + 1;

        $orgName = $target->targetOrganization?->name ?? "Target #{$target->id}";

        $task = Task::create([
            'uuid'            => (string) Str::uuid(),
            'organization_id' => $target->organization_id,
            'project_id'      => $target->project_id,
            'parent_id'       => null,
            'employee_id'     => $this->resolveEmployeeId($target),
            'title'           => "Phase [{$newPhase}] — {$orgName}",
            'description'     => "Tự động tạo khi chuyển phase → {$newPhase}.",
            'task_type'       => 'task',
            'status'          => 'todo',
            'priority'        => 'medium',
            'sort_order'      => $sortOrder,
            'depth'           => 0,
            'is_leaf'         => true,
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ]);

        DB::table('task_histories')->insert([
            'task_id'       => $task->id,
            'actor_id'      => auth()->id(),
            'field_changed' => 'created',
            'old_value'     => null,
            'new_value'     => $task->uuid,
            'changed_at'    => now(),
        ]);

        return $task;
    }

    private function resolveEmployeeId(DeploymentTarget $target): ?int
    {
        if (! $target->assigned_employee_id) {
            return null;
        }

        // Tasks use employee_id, not user_id — return directly
        return $target->assigned_employee_id;
    }
}
