<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Sop\Actions\Backend\DestroySopStepAction;
use Modules\Sop\Actions\Backend\StoreSopStepAction;
use Modules\Sop\Actions\Backend\UpdateSopStepAction;
use Modules\Sop\Enums\StepType;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Models\SopStep;

class SopStepController extends Controller
{
    public function store(Request $request, SopProcess $sop): JsonResponse
    {
        $this->authorize('update', $sop);

        $data = $request->validate([
            'title'               => 'required|string|max:200',
            'step_type'           => 'required|string|in:' . implode(',', array_column(StepType::cases(), 'value')),
            'description'         => 'nullable|string',
            'expected_output'     => 'nullable|string',
            'warning_note'        => 'nullable|string',
            'duration_minutes'    => 'nullable|integer|min:1|max:32767',
            'is_mandatory'        => 'boolean',
            // BR-FC-004 + BR-FC-006: ref_sop phải approved và cùng org (not trusted from client)
            'ref_sop_id'          => [
                'nullable', 'integer',
                Rule::exists('sop_processes', 'id')
                    ->where('organization_id', TenantContext::getOrganizationId())
                    ->where('status', 'approved'),
            ],
            'branch_yes_position' => 'nullable|integer|min:1',
            'branch_no_position'  => 'nullable|integer|min:1',
        ]);

        $step = app(StoreSopStepAction::class)->handle($sop, $data);

        return response()->json($step, 201);
    }

    public function update(Request $request, SopProcess $sop, SopStep $step): JsonResponse
    {
        $this->authorize('update', $sop);

        abort_if($step->sop_id !== $sop->id, 404);

        $data = $request->validate([
            'title'               => 'required|string|max:200',
            'step_type'           => 'required|string|in:' . implode(',', array_column(StepType::cases(), 'value')),
            'description'         => 'nullable|string',
            'expected_output'     => 'nullable|string',
            'warning_note'        => 'nullable|string',
            'duration_minutes'    => 'nullable|integer|min:1|max:32767',
            'is_mandatory'        => 'boolean',
            // BR-FC-004 + BR-FC-006: ref_sop phải approved và cùng org (not trusted from client)
            'ref_sop_id'          => [
                'nullable', 'integer',
                Rule::exists('sop_processes', 'id')
                    ->where('organization_id', TenantContext::getOrganizationId())
                    ->where('status', 'approved'),
            ],
            'branch_yes_position' => 'nullable|integer|min:1',
            'branch_no_position'  => 'nullable|integer|min:1',
        ]);

        $step = app(UpdateSopStepAction::class)->handle($step, $data);

        return response()->json($step);
    }

    public function destroy(SopProcess $sop, SopStep $step): JsonResponse
    {
        $this->authorize('update', $sop);

        abort_if($step->sop_id !== $sop->id, 404);

        app(DestroySopStepAction::class)->handle($step);

        return response()->json(['message' => 'OK']);
    }
}
