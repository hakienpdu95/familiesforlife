<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\AssignChecklistItemData;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentProgressLog;
use Modules\Employee\Models\Employee;

class AssignChecklistItemAction
{
    use AsAction;

    public function handle(DeploymentChecklistItem $item, AssignChecklistItemData $data): void
    {
        $item->update(['assigned_employee_id' => $data->assigned_employee_id]);

        $label = $data->assigned_employee_id
            ? 'Chỉ định phụ trách: ' . (Employee::find($data->assigned_employee_id)?->full_name ?? '—')
            : 'Bỏ chỉ định phụ trách';

        DeploymentProgressLog::create([
            'organization_id'              => $item->organization_id,
            'deployment_target_id'         => $item->deployment_target_id,
            'deployment_checklist_item_id' => $item->id,
            'phase'                        => $item->phase,
            'percent'                      => DeploymentChecklistItem::phaseCompletionPct($item->deployment_target_id, $item->phase),
            'remark'                       => "{$label} — {$item->item_label}",
            'logged_by'                    => auth()->id(),
            'logged_at'                    => now(),
        ]);
    }
}
