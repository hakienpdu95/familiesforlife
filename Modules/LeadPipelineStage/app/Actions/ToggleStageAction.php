<?php

namespace Modules\LeadPipelineStage\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\LeadPipelineStage\Events\StageUpdated;
use Modules\LeadPipelineStage\Models\LeadPipelineStage;

class ToggleStageAction
{
    use AsAction;

    public function handle(LeadPipelineStage $stage): LeadPipelineStage
    {
        $stage->update(['is_active' => ! $stage->is_active]);
        $stage->refresh();

        event(new StageUpdated($stage));

        return $stage;
    }
}
