<?php

namespace Modules\Sop\Actions\Backend;

use Modules\Sop\Models\SopStepConnector;
use Modules\Sop\Repositories\SopFlowchartRepository;

class DestroySopStepConnectorAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopStepConnector $connector): void
    {
        $sopId = $connector->sop_id;
        $connector->delete();
        $this->repo->invalidate($sopId);
    }
}
