<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentProgressLog;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Notifications\ChecklistCompletedNotification;

class ToggleChecklistItemAction
{
    use AsAction;

    public function handle(DeploymentChecklistItem $item): void
    {
        if ($item->is_done) {
            $item->update([
                'is_done' => false,
                'done_by' => null,
                'done_at' => null,
            ]);
        } else {
            $item->update([
                'is_done' => true,
                'done_by' => auth()->id(),
                'done_at' => now(),
            ]);
        }

        // Auto-log phase progress after every toggle
        $this->logProgress($item);
    }

    private function logProgress(DeploymentChecklistItem $item): void
    {
        $targetId = $item->deployment_target_id;
        $phase    = $item->phase;
        $pct      = DeploymentChecklistItem::phaseCompletionPct($targetId, $phase);
        $verb     = $item->is_done ? 'Hoàn thành' : 'Bỏ hoàn thành';

        DeploymentProgressLog::create([
            'organization_id'              => $item->organization_id,
            'deployment_target_id'         => $targetId,
            'deployment_checklist_item_id' => $item->id,
            'phase'                        => $phase,
            'percent'                      => $pct,
            'remark'                       => "{$verb}: {$item->item_label}",
            'logged_by'                    => auth()->id(),
            'logged_at'                    => now(),
        ]);

        // Notify when phase checklist reaches 100%
        if ($pct === 100 && $item->is_done) {
            $target = DeploymentTarget::withoutTenant()->find($targetId);
            if ($target) {
                auth()->user()?->notify(new ChecklistCompletedNotification($target, $phase, $pct));
            }
        }
    }
}
