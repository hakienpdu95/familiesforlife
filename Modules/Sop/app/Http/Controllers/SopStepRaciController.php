<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Sop\Actions\Backend\DestroySopStepRaciAction;
use Modules\Sop\Actions\Backend\StoreSopStepRaciAction;
use Modules\Sop\Models\SopStep;
use Modules\Sop\Models\SopStepRaci;

class SopStepRaciController extends Controller
{
    public function index(SopStep $step): JsonResponse
    {
        $this->authorize('view', $step->sop);

        $raci = SopStepRaci::where('step_id', $step->id)
            ->get()
            ->map(fn ($r) => [
                'uuid'          => $r->uuid,
                'raci_type'     => $r->raci_type,
                'assignee_type' => $r->assignee_type,
                'assignee_id'   => $r->assignee_id,
                'assignee_name' => $r->assigneeName(),
                'notes'         => $r->notes,
            ]);

        return response()->json($raci);
    }

    public function store(Request $request, SopStep $step): JsonResponse
    {
        $this->authorize('update', $step->sop);

        $data = $request->validate([
            'assignee_type' => 'required|in:user,role',
            'assignee_id'   => 'required|integer|min:1',
            'raci_type'     => 'required|in:R,A,C,I',
            'notes'         => 'nullable|string|max:1000',
        ]);

        // Enforce unique constraint at app layer
        $exists = SopStepRaci::where('step_id', $step->id)
            ->where('assignee_type', $data['assignee_type'])
            ->where('assignee_id', $data['assignee_id'])
            ->where('raci_type', $data['raci_type'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Phân công RACI này đã tồn tại.'], 422);
        }

        $raci = app(StoreSopStepRaciAction::class)->handle($step, $data);

        return response()->json([
            'uuid'          => $raci->uuid,
            'raci_type'     => $raci->raci_type,
            'assignee_type' => $raci->assignee_type,
            'assignee_id'   => $raci->assignee_id,
            'assignee_name' => $raci->assigneeName(),
            'notes'         => $raci->notes,
        ], 201);
    }

    public function destroy(SopStep $step, SopStepRaci $raci): JsonResponse
    {
        $this->authorize('update', $step->sop);

        abort_if($raci->step_id !== $step->id, 404);

        app(DestroySopStepRaciAction::class)->handle($raci);

        return response()->json(['message' => 'OK']);
    }
}
