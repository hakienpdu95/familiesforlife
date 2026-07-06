<?php

namespace Modules\LeadPipelineStage\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadPipelineStage\Events\StageDeleted;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class DeleteStageAction
{
    use AsAction;

    public function handle(LeadPipelineStage $stage): void
    {
        if ($stage->is_global) {
            throw ValidationException::withMessages([
                'stage' => 'Không thể xóa tình trạng toàn hệ thống.',
            ]);
        }

        // Check if any leads are using this stage
        $leadsCount = \DB::table('leads')->where('stage_id', $stage->id)->count();
        if ($leadsCount > 0) {
            throw ValidationException::withMessages([
                'stage' => "Không thể xóa: có {$leadsCount} cơ hội đang dùng tình trạng này.",
            ]);
        }

        $stageId = $stage->id;
        $orgId   = $stage->organization_id;

        DB::transaction(fn () => $stage->delete());

        event(new StageDeleted($stageId, $orgId));
    }
}
