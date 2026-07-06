<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sop\Actions\Backend\DestroySopStepConnectorAction;
use Modules\Sop\Actions\Backend\StoreSopStepConnectorAction;
use Modules\Sop\Actions\Backend\UpdateSopStepConnectorAction;
use Modules\Sop\Enums\ConnectorType;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStepConnector;

class SopStepConnectorController extends Controller
{
    public function store(Request $request, SopProcess $sop): JsonResponse
    {
        $this->authorize('update', $sop);

        $types = implode(',', array_column(ConnectorType::cases(), 'value'));

        $data = $request->validate([
            'from_step_id'   => 'required|integer',
            'to_step_id'     => 'required|integer',
            'connector_type' => "required|string|in:{$types}",
            'label'          => 'nullable|string|max:60',
            'color_hex'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'sort_order'     => 'nullable|integer',
        ]);

        $connector = app(StoreSopStepConnectorAction::class)->handle($sop, $data);

        return response()->json($connector, 201);
    }

    public function update(Request $request, SopProcess $sop, SopStepConnector $connector): JsonResponse
    {
        $this->authorize('update', $sop);

        abort_if($connector->sop_id !== $sop->id, 404);

        $types = implode(',', array_column(ConnectorType::cases(), 'value'));

        $data = $request->validate([
            'connector_type' => "nullable|string|in:{$types}",
            'label'          => 'nullable|string|max:60',
            'color_hex'      => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $connector = app(UpdateSopStepConnectorAction::class)->handle($connector, $data);

        return response()->json($connector);
    }

    public function destroy(SopProcess $sop, SopStepConnector $connector): JsonResponse
    {
        $this->authorize('update', $sop);

        abort_if($connector->sop_id !== $sop->id, 404);

        app(DestroySopStepConnectorAction::class)->handle($connector);

        return response()->json(['message' => 'OK']);
    }
}
