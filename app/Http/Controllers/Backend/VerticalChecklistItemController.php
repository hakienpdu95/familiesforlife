<?php

namespace App\Http\Controllers\Backend;

use App\Foundation\Vertical\VerticalChecklistItem;
use App\Foundation\Vertical\VerticalPhase;
use App\Foundation\Vertical\VerticalTemplate;
use App\Foundation\VerticalRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Survey\Actions\ReorderAction;

class VerticalChecklistItemController extends Controller
{
    public function store(Request $request, VerticalTemplate $verticalTemplate, VerticalPhase $phase): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);
        abort_unless($phase->vertical_template_id === $verticalTemplate->id, 404);

        $validated = $this->validateItem($request, $phase);

        $item = $phase->checklistItems()->create([
            'key'         => $validated['key'],
            'label'       => $validated['label'],
            'is_required' => $validated['is_required'],
            'sort_order'  => ($phase->checklistItems()->max('sort_order') ?? -1) + 1,
        ]);

        $this->clearCache($verticalTemplate);

        return response()->json([
            'success' => true,
            'data'    => $this->payload($item),
            'message' => "Mục checklist \"{$item->label}\" đã được thêm.",
        ]);
    }

    public function update(Request $request, VerticalTemplate $verticalTemplate, VerticalPhase $phase, VerticalChecklistItem $item): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);
        abort_unless($phase->vertical_template_id === $verticalTemplate->id, 404);
        abort_unless($item->vertical_phase_id === $phase->id, 404);

        $validated = $this->validateItem($request, $phase, $item->id);

        $item->update([
            'key'         => $validated['key'],
            'label'       => $validated['label'],
            'is_required' => $validated['is_required'],
        ]);

        $this->clearCache($verticalTemplate);

        return response()->json([
            'success' => true,
            'data'    => $this->payload($item->fresh()),
            'message' => 'Mục checklist đã được cập nhật.',
        ]);
    }

    public function destroy(VerticalTemplate $verticalTemplate, VerticalPhase $phase, VerticalChecklistItem $item): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);
        abort_unless($phase->vertical_template_id === $verticalTemplate->id, 404);
        abort_unless($item->vertical_phase_id === $phase->id, 404);

        $label = $item->label;
        $item->delete();

        $this->clearCache($verticalTemplate);

        return response()->json(['success' => true, 'message' => "Mục checklist \"{$label}\" đã bị xóa."]);
    }

    public function reorder(Request $request, VerticalTemplate $verticalTemplate, VerticalPhase $phase, ReorderAction $action): JsonResponse
    {
        $this->authorize('update', $verticalTemplate);
        abort_unless($phase->vertical_template_id === $verticalTemplate->id, 404);

        $validated = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => [
                'required', 'integer',
                Rule::exists('vertical_checklist_items', 'id')->where('vertical_phase_id', $phase->id),
            ],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        $action->handle('vertical_checklist_items', $validated['items']);
        $this->clearCache($verticalTemplate);

        return response()->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function validateItem(Request $request, VerticalPhase $phase, ?int $exceptId = null): array
    {
        $validated = $request->validate([
            'key'   => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/',
                function ($attribute, $value, $fail) use ($phase, $exceptId) {
                    $exists = $phase->checklistItems()
                        ->where('key', $value)
                        ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
                        ->exists();
                    if ($exists) $fail('Key checklist item này đã tồn tại trong phase.');
                },
            ],
            'label'       => ['required', 'string', 'max:255'],
            'is_required' => ['boolean'],
        ]);

        $validated['is_required'] = (bool) ($validated['is_required'] ?? false);

        return $validated;
    }

    private function clearCache(VerticalTemplate $verticalTemplate): void
    {
        VerticalRegistry::clearCache($verticalTemplate->organization_id, $verticalTemplate->code);
    }

    private function payload(VerticalChecklistItem $item): array
    {
        return [
            'id'                => $item->id,
            'vertical_phase_id' => $item->vertical_phase_id,
            'key'               => $item->key,
            'label'             => $item->label,
            'is_required'       => $item->is_required,
            'sort_order'        => $item->sort_order,
        ];
    }
}
