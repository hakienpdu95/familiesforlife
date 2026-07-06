<?php

namespace Modules\Sop\Actions\Backend;

use Modules\Sop\Models\SopStep;
use Modules\Sop\Repositories\SopFlowchartRepository;

class UpdateSopStepAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopStep $step, array $data): SopStep
    {
        $step->update([
            'title'               => $data['title'],
            'description'         => $data['description'] ?? null,
            'expected_output'     => $data['expected_output'] ?? null,
            'warning_note'        => $data['warning_note'] ?? null,
            'step_type'           => $data['step_type'],
            'ref_sop_id'          => $data['ref_sop_id'] ?? null,
            'branch_yes_position' => $data['branch_yes_position'] ?? null,
            'branch_no_position'  => $data['branch_no_position'] ?? null,
            'duration_minutes'    => $data['duration_minutes'] ?: null,
            'is_mandatory'        => $data['is_mandatory'] ?? true,
            'updated_by'          => auth()->id(),
        ]);

        $this->repo->invalidate($step->sop_id);

        return $step->fresh();
    }
}
