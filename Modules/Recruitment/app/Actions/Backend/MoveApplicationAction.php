<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Data\Requests\MoveApplicationData;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\Recruitment\Enums\PipelineStageType;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcApplicationStageLog;
use Modules\Recruitment\Models\RcPipelineStage;

class MoveApplicationAction
{
    use AsAction;

    public function handle(RcApplication $application, MoveApplicationData $data): RcApplication
    {
        $newStage = RcPipelineStage::findOrFail($data->stage_id);

        // BR-RC-003: require_score check (phase 3 will add evaluations)
        // For phase 1 we log the move and update stage

        // Record immutable audit log
        RcApplicationStageLog::create([
            'application_id' => $application->id,
            'stage_id'       => $newStage->id,
            'result'         => $data->result,
            'note'           => $data->note,
            'actioned_by'    => auth()->id(),
        ]);

        // Update current stage
        $application->current_stage_id = $newStage->id;

        // Handle terminal stages
        if ($newStage->stage_type->isTerminal()) {
            $application->status = $newStage->stage_type === PipelineStageType::Hired
                ? ApplicationStatus::Hired
                : ApplicationStatus::Rejected;
        }

        $application->save();

        return $application->load('currentStage');
    }
}
