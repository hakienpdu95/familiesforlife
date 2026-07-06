<?php

namespace App\Http\Controllers\Backend;

use App\Foundation\Vertical\VerticalPhase;
use App\Foundation\Vertical\VerticalTemplate;
use App\Foundation\VerticalRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Survey\Actions\ReorderAction;

class VerticalPhaseController extends Controller
{
    public function store(Request $request, VerticalTemplate $verticalTemplate): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);

        $validated = $this->validatePhase($request, $verticalTemplate);

        $phase = DB::transaction(function () use ($verticalTemplate, $validated) {
            if ($validated['is_initial']) {
                $verticalTemplate->phases()->update(['is_initial' => false]);
            }

            return $verticalTemplate->phases()->create([
                'key'                          => $validated['key'],
                'label'                        => $validated['label'],
                'is_initial'                   => $validated['is_initial'],
                'auto_assign_data_collection'  => $validated['auto_assign_data_collection'],
                'sort_order'                   => ($verticalTemplate->phases()->max('sort_order') ?? -1) + 1,
            ]);
        });

        $this->clearCache($verticalTemplate);

        return response()->json([
            'success' => true,
            'data'    => $this->payload($phase),
            'message' => "Phase \"{$phase->label}\" đã được thêm.",
        ]);
    }

    public function update(Request $request, VerticalTemplate $verticalTemplate, VerticalPhase $phase): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);
        abort_unless($phase->vertical_template_id === $verticalTemplate->id, 404);

        $validated = $this->validatePhase($request, $verticalTemplate, $phase->id);

        DB::transaction(function () use ($verticalTemplate, $phase, $validated) {
            if ($validated['is_initial']) {
                $verticalTemplate->phases()->where('id', '!=', $phase->id)->update(['is_initial' => false]);
            }

            $phase->update([
                'key'                          => $validated['key'],
                'label'                        => $validated['label'],
                'is_initial'                   => $validated['is_initial'],
                'auto_assign_data_collection'  => $validated['auto_assign_data_collection'],
            ]);
        });

        $this->clearCache($verticalTemplate);

        return response()->json([
            'success' => true,
            'data'    => $this->payload($phase->fresh()),
            'message' => 'Phase đã được cập nhật.',
        ]);
    }

    public function destroy(VerticalTemplate $verticalTemplate, VerticalPhase $phase): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);
        abort_unless($phase->vertical_template_id === $verticalTemplate->id, 404);

        $label = $phase->label;
        $phase->delete();

        $this->clearCache($verticalTemplate);

        return response()->json(['success' => true, 'message' => "Phase \"{$label}\" đã bị xóa."]);
    }

    public function reorder(Request $request, VerticalTemplate $verticalTemplate, ReorderAction $action): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);

        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => [
                'required', 'integer',
                Rule::exists('vertical_phases', 'id')->where('vertical_template_id', $verticalTemplate->id),
            ],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        $action->handle('vertical_phases', $validated['items']);
        $this->clearCache($verticalTemplate);

        return response()->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function validatePhase(Request $request, VerticalTemplate $verticalTemplate, ?int $exceptId = null): array
    {
        $validated = $request->validate([
            'key'                          => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
                function ($attribute, $value, $fail) use ($verticalTemplate, $exceptId) {
                    $exists = $verticalTemplate->phases()
                        ->where('key', $value)
                        ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                        ->exists();
                    if ($exists) $fail('Key phase này đã tồn tại trong template.');
                },
            ],
            'label'                        => ['required', 'string', 'max:100'],
            'is_initial'                   => ['boolean'],
            'auto_assign_data_collection'  => ['boolean'],
        ]);

        $validated['is_initial']                  = (bool) ($validated['is_initial'] ?? false);
        $validated['auto_assign_data_collection']  = (bool) ($validated['auto_assign_data_collection'] ?? false);

        return $validated;
    }

    private function clearCache(VerticalTemplate $verticalTemplate): void
    {
        VerticalRegistry::clearCache($verticalTemplate->organization_id, $verticalTemplate->code);
    }

    private function payload(VerticalPhase $phase): array
    {
        return [
            'id'                           => $phase->id,
            'vertical_template_id'         => $phase->vertical_template_id,
            'key'                          => $phase->key,
            'label'                        => $phase->label,
            'sort_order'                   => $phase->sort_order,
            'is_initial'                   => $phase->is_initial,
            'auto_assign_data_collection'  => $phase->auto_assign_data_collection,
        ];
    }
}
