<?php

namespace Modules\Sop\Actions\Backend;

use Illuminate\Support\Str;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Repositories\SopFlowchartRepository;

class StoreSopStepAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopProcess $sop, array $data): SopStep
    {
        $maxPosition = SopStep::where('sop_id', $sop->id)
            ->where('is_active', true)
            ->max('position') ?? 0;

        $step = SopStep::create([
            'uuid'                => Str::uuid(),
            'sop_id'              => $sop->id,
            'position'            => $maxPosition + 1,
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
            'is_active'           => true,
            'created_by'          => auth()->id(),
            'updated_by'          => auth()->id(),
        ]);

        $this->repo->invalidate($sop->id);

        return $step;
    }
}
