<?php

namespace Modules\Sop\Actions\Backend;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepConnector;
use Modules\Sop\Repositories\SopFlowchartRepository;

class StoreSopStepConnectorAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopProcess $sop, array $data): SopStepConnector
    {
        $fromStep = SopStep::where('id', $data['from_step_id'])
            ->where('sop_id', $sop->id)
            ->where('is_active', true)
            ->firstOrFail();

        $toStep = SopStep::where('id', $data['to_step_id'])
            ->where('sop_id', $sop->id)
            ->where('is_active', true)
            ->firstOrFail();

        $exists = SopStepConnector::where('from_step_id', $fromStep->id)
            ->where('to_step_id', $toStep->id)
            ->where('connector_type', $data['connector_type'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'connector_type' => 'Kết nối này đã tồn tại.',
            ]);
        }

        $connector = SopStepConnector::create([
            'uuid'           => Str::uuid(),
            'sop_id'         => $sop->id,
            'from_step_id'   => $fromStep->id,
            'to_step_id'     => $toStep->id,
            'connector_type' => $data['connector_type'],
            'label'          => $data['label'] ?? null,
            'color_hex'      => $data['color_hex'] ?? null,
            'sort_order'     => $data['sort_order'] ?? 0,
        ]);

        $this->repo->invalidate($sop->id);

        return $connector;
    }
}
