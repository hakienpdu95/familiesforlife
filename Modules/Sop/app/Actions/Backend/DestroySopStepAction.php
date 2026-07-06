<?php

namespace Modules\Sop\Actions\Backend;

use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepConnector;
use Modules\Sop\Repositories\SopFlowchartRepository;

class DestroySopStepAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopStep $step): void
    {
        $sopId = $step->sop_id;

        // Remove all connectors touching this step before soft-deleting
        SopStepConnector::where('from_step_id', $step->id)
            ->orWhere('to_step_id', $step->id)
            ->delete();

        $step->update([
            'is_active'  => false,
            'updated_by' => auth()->id(),
        ]);

        $this->repo->invalidate($sopId);
    }
}
