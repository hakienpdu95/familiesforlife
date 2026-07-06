<?php

namespace Modules\Sop\Actions\Backend;

use Modules\Sop\Models\SopStepRaci;
use Modules\Sop\Repositories\SopFlowchartRepository;

class DestroySopStepRaciAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopStepRaci $raci): void
    {
        $sopId = $raci->step->sop_id;
        $raci->delete();
        $this->repo->invalidate($sopId);
    }
}
