<?php

namespace Modules\Sop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Sop\Models\SopProcess;
use Modules\Sop\Repositories\SopFlowchartRepository;

class SopStepReorderController extends Controller
{
    public function update(Request $request, SopProcess $sop): JsonResponse
    {
        $this->authorize('update', $sop);

        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer',
        ]);

        $orderedIds = $data['ids'];

        DB::transaction(function () use ($sop, $orderedIds) {
            $cases = collect($orderedIds)
                ->map(fn ($id, $i) => "WHEN {$id} THEN " . ($i + 1))
                ->implode(' ');

            $placeholders = implode(', ', array_fill(0, count($orderedIds), '?'));

            DB::statement(
                "UPDATE sop_steps
                 SET position = CASE id {$cases} END,
                     updated_at = NOW()
                 WHERE sop_id = ?
                   AND id IN ({$placeholders})",
                [$sop->id, ...$orderedIds]
            );
        });

        app(SopFlowchartRepository::class)->invalidate($sop->id);

        return response()->json(['message' => 'OK']);
    }
}
