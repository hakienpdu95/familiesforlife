<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\AddChecklistNoteData;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentProgressLog;

class AddChecklistNoteAction
{
    use AsAction;

    /**
     * Ghi chú riêng cho 1 mục checklist — không đổi trạng thái is_done, chỉ thêm
     * 1 dòng nhật ký vào lịch sử của đúng mục đó (khác với toggle, có thể gọi bất kỳ lúc nào).
     */
    public function handle(DeploymentChecklistItem $item, AddChecklistNoteData $data): DeploymentProgressLog
    {
        $pct = DeploymentChecklistItem::phaseCompletionPct($item->deployment_target_id, $item->phase);

        return DeploymentProgressLog::create([
            'organization_id'              => $item->organization_id,
            'deployment_target_id'         => $item->deployment_target_id,
            'deployment_checklist_item_id' => $item->id,
            'phase'                        => $item->phase,
            'percent'                      => $pct,
            'remark'                       => $data->note,
            'logged_by'                    => auth()->id(),
            'logged_at'                    => now(),
        ]);
    }
}
