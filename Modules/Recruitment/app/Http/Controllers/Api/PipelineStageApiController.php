<?php

namespace Modules\Recruitment\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Recruitment\Models\RcPipelineStage;

class PipelineStageApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Recruitment\Models\RcApplication::class);

        $stages = RcPipelineStage::query()
            ->active()
            ->ordered()
            ->get(['id', 'uuid', 'name', 'stage_type', 'sort_order', 'color_hex', 'require_score']);

        return response()->json(['data' => $stages]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('manage', \Modules\Recruitment\Models\RcPipelineStage::class);

        $validated = $request->validate([
            'stages'          => ['required', 'array'],
            'stages.*.id'     => ['required', 'integer'],
            'stages.*.order'  => ['required', 'integer'],
        ]);

        foreach ($validated['stages'] as $item) {
            RcPipelineStage::where('id', $item['id'])
                ->update(['sort_order' => $item['order']]);
        }

        return response()->json(['message' => 'Đã cập nhật thứ tự']);
    }
}
