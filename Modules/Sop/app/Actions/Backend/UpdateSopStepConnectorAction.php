<?php

namespace Modules\Sop\Actions\Backend;

use Modules\Sop\Models\SopStepConnector;
use Modules\Sop\Repositories\SopFlowchartRepository;

class UpdateSopStepConnectorAction
{
    public function __construct(private readonly SopFlowchartRepository $repo) {}

    public function handle(SopStepConnector $connector, array $data): SopStepConnector
    {
        $connector->update([
            'connector_type' => $data['connector_type'] ?? $connector->connector_type,
            'label'          => $data['label'] ?? null,
            'color_hex'      => $data['color_hex'] ?? null,
        ]);

        $this->repo->invalidate($connector->sop_id);

        return $connector->fresh();
    }
}
