<?php

namespace Modules\EntityComparison\Features\Comparison\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\EntityComparison\Features\Comparison\Actions\BuildComparisonTableAction;
use Modules\EntityComparison\Models\EntityType;

/**
 * spec/Entity_Comparison_Module_Technical_Spec.md §7.2 bước 2-3, §8 — trang so sánh side-by-side,
 * công khai. Số lượng Entity so sánh: min/max đọc từ config('entitycomparison.min_compare_entities'/
 * 'max_compare_entities') — không hard-code (§9).
 */
class EntityComparisonController extends Controller
{
    public function show(Request $request, EntityType $entityType, BuildComparisonTableAction $action): View
    {
        $uuids = array_values(array_unique((array) $request->query('compare', [])));
        $min = (int) config('entity_comparison.min_compare_entities');
        $max = (int) config('entity_comparison.max_compare_entities');

        if (count($uuids) < $min || count($uuids) > $max) {
            return redirect()
                ->route('entity_comparison.public.index', $entityType)
                ->with('error', "Vui lòng chọn từ {$min} đến {$max} đối tượng để so sánh.");
        }

        $result = $action->handle($entityType, $uuids);

        return view('entitycomparison::public.compare', [
            'entityType' => $entityType,
            'entities' => $result['entities'],
            'rows' => $result['rows'],
        ]);
    }

    /**
     * JSON cho AJAX re-render khi user thêm/bớt Entity trên trang so sánh mà không reload trang.
     * spec/Entity_Comparison_Module_Technical_Spec.md §8 — route KHÔNG có {entityType} trong path
     * (`api/entity-comparison/compare`), entity_type_id nằm trong body — khác route `show()` ở
     * trên (đọc EntityType qua route binding trang HTML theo slug).
     */
    public function compareApi(Request $request, BuildComparisonTableAction $action): JsonResponse
    {
        $validated = $request->validate([
            'entity_type_id' => ['required', 'integer', 'exists:entity_types,id'],
            'entity_uuids' => ['required', 'array'],
            'entity_uuids.*' => ['string'],
        ]);

        $entityType = EntityType::findOrFail($validated['entity_type_id']);

        $min = (int) config('entity_comparison.min_compare_entities');
        $max = (int) config('entity_comparison.max_compare_entities');
        $uuids = array_values(array_unique($validated['entity_uuids']));

        if (count($uuids) < $min || count($uuids) > $max) {
            return response()->json(['message' => "Vui lòng chọn từ {$min} đến {$max} đối tượng."], 422);
        }

        $result = $action->handle($entityType, $uuids);

        return response()->json([
            'entities' => $result['entities']->map(fn ($entity) => [
                'uuid' => $entity->uuid,
                'name' => $entity->name,
                'cover_url' => $entity->getMediaUrl('cover', 'thumb'),
            ]),
            'rows' => collect($result['rows'])->map(fn (array $row) => [
                'criterion' => [
                    'name' => $row['criterion']->name,
                    'unit' => $row['criterion']->unit,
                    'is_deprecated' => $row['criterion']->trashed(),
                ],
                'cells' => $row['cells'],
            ]),
        ]);
    }
}
