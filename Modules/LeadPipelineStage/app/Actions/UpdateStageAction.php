<?php

namespace Modules\LeadPipelineStage\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadPipelineStage\Data\Requests\UpdateStageData;
use Modules\LeadPipelineStage\Events\StageUpdated;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class UpdateStageAction
{
    use AsAction;

    public function handle(LeadPipelineStage $stage, UpdateStageData $data): LeadPipelineStage
    {
        $updated = DB::transaction(function () use ($stage, $data) {
            $stage->update([
                'label'       => $data->label,
                'color'       => $data->color,
                'sort_order'  => $data->sort_order,
                'probability' => $data->probability,
                'is_won'      => $data->is_won,
                'is_lost'     => $data->is_lost,
            ]);

            return $stage->fresh();
        });

        event(new StageUpdated($updated));

        return $updated;
    }
}
