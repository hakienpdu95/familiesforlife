<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\AssignTargetEmployeeData;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentProgressLog;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Employee\Models\Employee;

class AssignTargetEmployeeAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, AssignTargetEmployeeData $data): void
    {
        $target->update(['assigned_employee_id' => $data->assigned_employee_id]);

        $label = $data->assigned_employee_id
            ? 'Chỉ định người phụ trách: ' . (Employee::find($data->assigned_employee_id)?->full_name ?? '—')
            : 'Bỏ chỉ định người phụ trách';

        DeploymentProgressLog::create([
            'organization_id'      => $target->organization_id,
            'deployment_target_id' => $target->id,
            'phase'                => $target->current_phase,
            'percent'              => DeploymentChecklistItem::phaseCompletionPct($target->id, $target->current_phase),
            'remark'               => $label,
            'logged_by'            => auth()->id(),
            'logged_at'            => now(),
        ]);
    }
}
