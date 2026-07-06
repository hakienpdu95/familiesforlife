<?php

namespace Modules\Sop\Actions\Backend;

use Illuminate\Support\Str;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepRaci;
use Modules\Sop\Repositories\SopFlowchartRepository;

class StoreSopStepRaciAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopStep $step, array $data): SopStepRaci
    {
        $raci = SopStepRaci::create([
            'uuid'          => Str::uuid(),
            'step_id'       => $step->id,
            'assignee_type' => $data['assignee_type'],
            'assignee_id'   => $data['assignee_id'],
            'raci_type'     => $data['raci_type'],
            'notes'         => $data['notes'] ?? null,
        ]);

        $this->repo->invalidate($step->sop_id);

        return $raci;
    }
}
